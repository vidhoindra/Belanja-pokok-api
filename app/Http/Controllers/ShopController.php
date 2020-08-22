<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Provinces as ProvinceResourceCollection;
use App\Http\Resources\Cities as CityResourceCollection;
use App\Models\Province;
use App\Models\City;
use App\Models\Product;
use App\Models\Order;
use App\Models\ProductOrder;
use Auth;
use DB;

class ShopController extends Controller
{
    public function provinces()
    {
        return new ProvinceResourceCollection(Province::get());
    }

    public function cities()
    {
        return new CityResourceCollection(City::get());
    }

    public function shipping(Request $request)
    {
        $user = Auth::user();
        $status = "error";
        $message = "";
        $data = null;
        $code = 200;
        if ($user) {
            $this->validate($request, [
                'name' => 'required',
                'address' => 'required',
                'phone' => 'required',
                'province_id' => 'required',
                'city_id' => 'required'
            ]);
            $user->name = $request->name;
            $user->address = $request->address;
            $user->phone = $request->phone;
            $user->province_id = $request->province_id;
            $user->city_id = $request->city_id;
            if ($user->save()) {
                $status = "success";
                $message = "Update shipping success";
                $data = $user->toArray();
            } else {
                $message = "Update shipping failed";
            }
        } else {
            $message = "User not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function couriers()
    {
        $couriers = [
            ['id' => 'jne', 'text' => 'JNE'],
            ['id' => 'tiki', 'text' => 'TIKI'],
            ['id' => 'pos', 'text' => 'POS']
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'couriers',
            'data' => $couriers
        ], 200);
    }

    public function services(Request $request)
    {
        $status = "error";
        $message = "";
        $data = [];
        $this->validate($request, [
            'courier' => 'required',
            'carts' => 'required'
        ]);
        $user = Auth::user();
        if ($user) {
            $destination = $user->city_id;
            if ($destination>0) {
                $origin = 153;
                $courier = $request->courier;
                $carts = $request->carts;
                $carts = json_decode($carts, true);
                // validation
                $validCart = $this->validateCart($carts);
                $data['safe_carts'] = $validCart['safe_carts'];
                $data['total'] = $validCart['total'];
                $quantity_different = $data['total']['quantity_before']<>$data['total']['quantity'];
                $weight = $validCart['total']['weight'] * 1000;
                if ($weight>0) {
                    $paramter = [
                        "origin" => $origin,
                        "destination" => $destination,
                        "weight" => $weight,
                        "courier" => $courier
                    ];
                    $response_services = $this->getServices($paramter);
                    if ($response_services['error']==null) {
                        $services = [];
                        $response = json_decode($response_services['response']);
                        $costs = $response->rajaongkir->results[0]->costs;
                        foreach ($costs as $cost) {
                            $service_name = $cost->service;
                            $service_cost = $cost->cost[0]->value;
                            $service_estimation = str_replace('hari', '', trim($cost->cost[0]->etd));
                            $services[] = [
                                'service' => $service_name,
                                'cost' => $service_cost,
                                'estimation' => $service_estimation,
                                'resume' => $service_name . ' [ Rp. ' . number_format($service_cost) . ', Etd: ' . $cost->cost[0]->etd . ' day(s) ]'
                            ];
                        }

                        if (count($services)>0) {
                            $data['services'] = $services;
                            $status = "success";
                            $message = "getting services success";
                        } else {
                            $message = "courier services unavailable";
                        }

                        if ($quantity_different) {
                            $status = "warning";
                            $message = "Check cart data, " . $message;
                        }
                    } else {
                        $message = "cURL Error #:" . $response_services['error'];
                    }
                } else {
                    $message = "weight invalid";
                }
            } else {
                $message = "destination not set";
            }
        } else {
            $message = "user not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    protected function validateCart($carts)
    {
        $safe_carts = [];
        $total = [
            'quantity_before' => 0,
            'quantity' => 0,
            'price' => 0,
            'weight' => 0
        ];
        $idx = 0;
        foreach ($carts as $cart) {
            $id = (int)$cart['id'];
            $quantity = (int)$cart['quantity'];
            $total['quantity_before'] += $quantity;
            $product = Product::find($id);
            if ($product) {
                if ($product->stock>0) {
                    $safe_carts[$idx]['id'] = $product->id;
                    $safe_carts[$idx]['product'] = $product->name;
                    $safe_carts[$idx]['cover'] = $product->cover;
                    $safe_carts[$idx]['price'] = $product->price;
                    $safe_carts[$idx]['weight'] = $product->weight;
                    if ($product->stock < $quantity) {
                        $quantity = (int) $product->stock;
                    }
                    $safe_carts[$idx]['quantity'] = $quantity;
                    $total['quantity'] += $quantity;
                    $total['price'] += $product->price * $quantity;
                    $total['weight'] += $product->weight * $quantity;
                    $idx++;
                } else {
                    continue;
                }
            }
        }

        return [
            'safe_carts' => $safe_carts,
            'total' => $total
        ];
    }

    protected function getServices($data)
    {
        $url_cost = "https://api.rajaongkir.com/starter/cost";
        $postdata = http_build_query($data);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url_cost,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded",
                "key: 0561caa0dc4415e28e79eb5efccab0e7"
            ]
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        return [
            'error' => $error,
            'response' => $response
        ];
    }

    public function payment(Request $request)
    {
        $error = 0;
        $status = "error";
        $message = "";
        $data = [];
        $user = Auth::user();
        if ($user) {
            $this->validate($request, [
                'courier' => 'required',
                'service' => 'required',
                'carts' => 'required'
            ]);

            DB::beginTransaction();
            try {
                $origin = 153;
                $destination = $user->city_id;
                if ($destination<=0) {
                    $error++;
                }
                $courier = $request->courier;
                $service = $request->service;
                $carts = json_decode($request->carts, true);
                // create order
                $order = new Order;
                $order->user_id = $user->id;
                $order->total_price = 0;
                $order->invoice_number = date('YmdHis');
                $order->courier_service = $courier . '_' . $service;
                $order->status = 'SUBMIT';
                if ($order->save()) {
                    $total_price = 0;
                    $total_weight = 0;
                    foreach ($carts as $cart) {
                        $id = (int)$cart['id'];
                        $quantity = (int)$cart['quantity'];
                        $product = Product::find($id);
                        if ($product) {
                            if ($product->stock >= $quantity) {
                                $total_price += $product->price * $quantity;
                                $total_weight += $product->weight * $quantity;
                                // create product order
                                $product_order = new ProductOrder;
                                $product_order->product_id = $product->id;
                                $product_order->order_id = $order->id;
                                $product_order->quantity = $quantity;
                                if ($product_order->save()) {
                                    $product->stock = $product->stock - $quantity;
                                    $product->save();
                                }
                            } else {
                                $error++;
                                throw new \Exception('Out of stock');
                            }
                        } else {
                            $error++;
                            throw new \Exception('Product is not found');
                        }
                    }

                    $totalBill = 0;
                    $weight = $total_weight * 1000;
                    if ($weight<=0) {
                        $error++;
                        throw new \Exception('Weight null');
                    }

                    $data = [
                        "origin" => $origin,
                        "destination" => $destination,
                        "weight" => $weight,
                        "courier" => $courier
                    ];

                    $data_cost = $this->getServices($data);
                    if ($data_cost['error']) {
                        $error++;
                        throw new \Exception('Courier service unavailable');
                    }

                    $response = json_decode($data_cost['response']);
                    $costs = $response->rajaongkir->results[0]->costs;
                    $service_cost = 0;
                    foreach ($costs as $cost) {
                        $service_name = $cost->service;
                        if ($service == $service_name) {
                            $service_cost = $cost->cost[0]->value;
                            break;
                        }
                    }
                    if ($service_cost<=0) {
                        $error++;
                        throw new \Exception('Service cost invalid');
                    }
                    $total_bill = $total_price + $service_cost;
                    // update total bill order
                    $order->total_price = $total_bill;
                    if ($order->save()) {
                        if ($error==0) {
                            DB::commit();
                            $status = 'success';
                            $message = 'Transaction success';
                            $data = [
                                'order_id' => $order->id,
                                'total_price' => $total_bill,
                                'invoice_number' => $order->invoice_number
                            ];
                        } else {
                            $message = 'There are ' . $error . ' errors';
                        }
                    }
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                DB::rollback();
            }
        } else {
            $message = "User not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public function myOrder(Request $request)
    {
        $user = Auth::user();
        $status = "error";
        $message = "";
        $data = [];
        if ($user) {
            $orders = Order::select('*')->where('user_id', '=', $user->id)->orderBy('id', 'DESC')->get();
            $status = "success";
            $message = "data my order";
            $data = $orders;
        } else {
            $message = "User not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public function detailOrder($orderId)
    {
        $user = Auth::user();
        $status = "error";
        $message = "";
        $data = [];
        if ($user) {
            $detailOrder = ProductOrder::where('order_id', '=', $orderId)
                            ->join('products', 'products.id', '=', 'product_order.product_id')
                            ->join('orders', 'orders.id', '=', 'product_order.order_id')
                            ->where('orders.user_id', '=', $user->id)
                            ->get();
            if ($detailOrder) {
                $status = "success";
                $message = "detail order";
                $data = $detailOrder;
            }
        } else {
            $message = "User not found";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], 200);
    }
}
