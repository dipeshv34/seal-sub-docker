<?php

namespace App\Http\Controllers;



use App\Models\User;
use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubApiController extends Controller
{
//    public function processApi(){
//
//        $response = Http::withHeaders([
//            'Authorization' => 'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
//        ])->get('https://api.hubapi.com/crm/v3/objects/contacts/example@gmail.com?idProperty=email');
//        $contact=$response->json();
//
//        $response = Http::withHeaders([
//            'Authorization' => 'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
//        ])->get('https://api.hubapi.com/crm/v3/objects/contacts/'.$contact['id'].'/associations/0-3');
//        $deal=$response->json();
//        $dealId=$deal['results'][0]['id'];
//
//
//        $body=["properties"=> ["membership_name"=> "platinum","deal_sku"=>2323,"membership_status"=>"active"]];
//
//        $response = Http::withHeaders([
//            'Authorization' => 'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
//        ])->patch('https://api.hubapi.com/crm/v3/objects/deals/'.$dealId,$body);
//
//        $productId=1221221;
//        $response=Http::withHeaders([
//         'Authorization'=>'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
//        ])->get(
//            'https://api.hubapi.com/crm/v3/objects/2-14139055/'.$productId.'?properties=seal_subscription_id&archived=false&idProperty=seal_subscription_id'
//        );
//        //object type == 2-15942972
//        dd($response->json());
//    }

    public function registerWebhooks(){
        $body=["topic"=> "subscription/created","address"=>'https://seal-subscription-y6hmz2knua-uc.a.run.app/subscription-webhook-created'];
//        $body=["topic"=> "subscription/created","address"=>'https://test-deep.free.beeceptor.com'];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
        ])->post('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks',$body);
//
        $body=["topic"=> "subscription/updated","address"=>'https://seal-subscription-y6hmz2knua-uc.a.run.app/subscription-webhook-updated'];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
        ])->post('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks',$body);
//
//
        $body=["topic"=> "subscription/cancelled","address"=>'https://seal-subscription-y6hmz2knua-uc.a.run.app/subscription-webhook-cancelled'];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
        ])->post('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks',$body);

        // To Delete a webhook
//        $response = Http::withHeaders([
//            'Content-Type' => 'application/json',
//            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
//        ])->delete('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks?id=3838');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
        ])->get('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks');
        dd($response->json());
    }

    public function subscriptionCreated(Request $request){
        try{
            $pubsub= new PubSubClient();
            $topic = $pubsub->topic('projects/hubspotintegration-388418/topics/subscription-created');
            $topic->publish(['data'=>json_encode($request->all())]);
            return response()->json(['message'=>'ok','status'=>true],200);
        }catch (\Exception $e){
            Log::info('<<<<>>>>>>> calling create topic --- '.$e->getMessage());
        }
    }
    public function subscriptionUpdated(Request $request){
        try{
            $pubsub= new PubSubClient();
            $topic = $pubsub->topic('projects/hubspotintegration-388418/topics/seal-subscription-update');
            $topic->publish(['data'=>json_encode($request->all())]);
            return response()->json(['message'=>'ok','status'=>true],200);
        }catch (\Exception $e){
            Log::info('<<<<>>>>>>> calling update topic --- '.$e->getMessage());
        }
    }

    public function subscriptionCancelled(Request $request){
        try{
            $pubsub= new PubSubClient();
            $topic = $pubsub->topic('projects/hubspotintegration-388418/topics/seal-subscription-cancelled');
            $topic->publish(['data'=>json_encode($request->all())]);
            return response()->json(['message'=>'ok','status'=>true],200);
        }catch (\Exception $e){
            Log::info('<<<<>>>>>>> calling cancel topic --- '.$e->getMessage());
        }
    }

    public function sealTopicSubscriptionCreated(Request $request){
        $data=collect(json_decode(base64_decode($request->message['data'])))->toArray();
        $cache=Cache::store('file')->get('subscription-created-'.$data['id']);
        if(empty($cache) || $cache!=true) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->get("https://api.hubapi.com/crm/v3/objects/2-15942972/" . $data['id'] . "?properties=seal_subscription_id,total_value&archived=false&idProperty=seal_subscription_id");
                $club = $response->json();
                $status = [
                    'ACTIVE' => 85863846,
                    'PAUSED' => 85863847,
                    'EXPIRED' => 85819956,
                    'CANCELLED' => 85819957

                ];
                if (empty($club)) {
                    $body = ["properties" => [
                        "seal_subscription_id" => $data['id'],
                        "order_placed" => $data['order_placed'],
                        "order_id" => $data['order_id'],
                        "email" => $data['email'],
                        "first_name" => $data['first_name'],
                        "last_name" => $data['last_name'],
                        "s_address1" => $data['s_address1'],
                        "s_address2" => $data['s_address2'],
                        "billing_interval" => $data['billing_interval'],
                        "city" => $data['s_city'],
                        "zip" => $data['s_zip'],
                        "country" => $data['s_country'],
                        "company" => $data['s_company'],
                        "total_value" => $data['total_value'],
                        "subscription_type" => $data['subscription_type'],
                        "status" => "ACTIVE",
                        "hs_pipeline" => 40569144,
                        "hs_pipeline_stage" => $status[$data['status']],
                        "customer_id" => $data['customer_id'],
                        "product_id" => $data['items'][0]->product_id,
                        "title" => $data['items'][0]->title,
                        "quantity" => $data['items'][0]->quantity,
                        "total_discount" => $data['items'][0]->total_discount,
                        "original_price" => $data['items'][0]->original_price,
                        "original_amount" => $data['items'][0]->original_amount,
                        "discount_value" => $data['items'][0]->discount_value,
                        "discount_amount" => $data['items'][0]->discount_amount,
                        "final_price" => $data['items'][0]->final_price,
                        "final_amount" => $data['items'][0]->final_amount,
                        "cancelled_on" => $data['cancelled_on'],
                        "paused_on" => $data['paused_on'],
                    ]
                    ];
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                    ])->post('https://api.hubapi.com/crm/v3/objects/2-15942972', $body);
                } else {
                    $body = ["properties" => [
                        "order_placed" => $data['order_placed'],
                        "order_id" => $data['order_id'],
                        "email" => $data['email'],
                        "first_name" => $data['first_name'],
                        "last_name" => $data['last_name'],
                        "s_address1" => $data['s_address1'],
                        "s_address2" => $data['s_address2'],
                        "city" => $data['s_city'],
                        "zip" => $data['s_zip'],
                        "country" => $data['s_country'],
                        "company" => $data['s_company'],
                        "billing_interval" => $data['billing_interval'],
                        "total_value" => $data['total_value'],
                        "subscription_type" => $data['subscription_type'],
                        "status" => $data['status'],
                        "hs_pipeline" => 40569144,
                        "hs_pipeline_stage" => $status[$data['status']],
                        "customer_id" => $data['customer_id'],
                        "product_id" => $data['items'][0]->product_id,
                        "title" => $data['items'][0]->title,
                        "quantity" => $data['items'][0]->quantity,
                        "total_discount" => $data['items'][0]->total_discount,
                        "original_price" => $data['items'][0]->original_price,
                        "original_amount" => $data['items'][0]->original_amount,
                        "discount_value" => $data['items'][0]->discount_value,
                        "discount_amount" => $data['items'][0]->discount_amount,
                        "final_price" => $data['items'][0]->final_price,
                        "final_amount" => $data['items'][0]->final_amount,
                        "cancelled_on" => $data['cancelled_on'],
                        "paused_on" => $data['paused_on'],
                    ]
                    ];
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                    ])->patch('https://api.hubapi.com/crm/v3/objects/2-15942972/' . $club['id'], $body);
                }
                $membership = $response->json();
//            //get contact
                $contact = Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->get('https://api.hubapi.com/crm/v3/objects/contacts/' . $data['email'] . '?idProperty=email');
                $contact = $contact->json();

                Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->put("https://api.hubapi.com/crm/v4/objects/2-15942972/" . $membership['id'] . "/associations/default/0-1/" . $contact['id']);

                //get Deal
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.hubapi.com/crm/v3/objects/deals/search?hapikey=',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
    "filterGroups": [
        {
            "filters": [
               {
          "operator": "EQ",
            "propertyName": "shopify_order_id",
            "value": "' . $data['order_id'] . '"
          }
            ]
        }
    ],
    "limit": 10,
    "properties": [
        "dealname",
        "shopify_order_id",
        "id"
    ]
}',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                    ),
                ));
                $curlResponse = json_decode(curl_exec($curl));
                curl_close($curl);
                Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->put("https://api.hubapi.com/crm/v4/objects/2-15942972/" . $membership['id'] . "/associations/default/0-3/" . $curlResponse->results[0]->id);
                Cache::store('file')->put('subscription-created-' . $data['id'], true, 180);
                return response()->json(['message' => 'ok', 'status' => true], 200);
            } catch (\Exception $e) {
                Log::info('message in catch >>>>>>>>>>' . $e->getMessage());
            }
        }
    }

    public function sealTopicSubscriptionUpdated(Request $request){
        $data=collect(json_decode(base64_decode($request->message['data'])))->toArray();
        Log::info(json_encode($data,JSON_PRETTY_PRINT));
        $cache=Cache::store('file')->get('subscription-updated-'.$data['id']);
        if(empty($cache) || $cache!=true){
            try{
                $status=[
                    'ACTIVE'=>85863846,
                    'PAUSED'=>85863847,
                    'EXPIRED'=>85819956,
                    'CANCELLED'=>85819957

                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->get("https://api.hubapi.com/crm/v3/objects/2-15942972/".$data['id']."?properties=seal_subscription_id,hs_pipeline_stage,total_value&archived=false&idProperty=seal_subscription_id");
                $club=$response->json();

                if(empty($club)) {
                    $body=["properties"=> [
                        "seal_subscription_id"=>$data['id'],
                        "order_placed"=>$data['order_placed'],
                        "order_id"=>$data['order_id'],
                        "email"=>$data['email'],
                        "first_name"=>$data['first_name'],
                        "last_name"=>$data['last_name'],
                        "s_address1"=>$data['s_address1'],
                        "s_address2"=>$data['s_address2'],
                        "billing_interval"=>$data['billing_interval'],
                        "city"=>$data['s_city'],
                        "zip"=>$data['s_zip'],
                        "country"=>$data['s_country'],
                        "company"=>$data['s_company'],
                        "total_value"=>$data['total_value'],
                        "subscription_type"=>$data['subscription_type'],
                        "status"=>$data['status'],
                        "hs_pipeline"=>40569144,
                        "hs_pipeline_stage"=>$status[$data['status']],
                        "customer_id"=>$data['customer_id'],
                        "product_id"=>$data['items'][0]->product_id,
                        "title"=>$data['items'][0]->title,
                        "quantity"=>$data['items'][0]->quantity,
                        "total_discount"=>$data['items'][0]->total_discount,
                        "original_price"=>$data['items'][0]->original_price,
                        "original_amount"=>$data['items'][0]->original_amount,
                        "discount_value"=>$data['items'][0]->discount_value,
                        "discount_amount"=>$data['items'][0]->discount_amount,
                        "final_price"=>$data['items'][0]->final_price,
                        "final_amount"=>$data['items'][0]->final_amount,
                        "cancelled_on"=> $data['cancelled_on'],
                        "paused_on"=> $data['paused_on'],
                    ]
                    ];
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                    ])->post('https://api.hubapi.com/crm/v3/objects/2-15942972',$body);
                }
                else{
                    $body=["properties"=> [
                        "order_placed"=>$data['order_placed'],
                        "order_id"=>$data['order_id'],
                        "email"=>$data['email'],
                        "first_name"=>$data['first_name'],
                        "last_name"=>$data['last_name'],
                        "s_address1"=>$data['s_address1'],
                        "s_address2"=>$data['s_address2'],
                        "city"=>$data['s_city'],
                        "zip"=>$data['s_zip'],
                        "country"=>$data['s_country'],
                        "company"=>$data['s_company'],
                        "billing_interval"=>$data['billing_interval'],
                        "subscription_type"=>$data['subscription_type'],
                        "status"=>$data['status'],
                        "hs_pipeline"=>40569144,
                        "hs_pipeline_stage"=>$status[$data['status']],
                        "customer_id"=>$data['customer_id'],
                        "product_id"=>$data['items'][0]->product_id,
                        "title"=>$data['items'][0]->title,
                        "quantity"=>$data['items'][0]->quantity,
                        "total_discount"=>$data['items'][0]->total_discount,
                        "original_price"=>$data['items'][0]->original_price,
                        "original_amount"=>$data['items'][0]->original_amount,
                        "discount_value"=>$data['items'][0]->discount_value,
                        "discount_amount"=>$data['items'][0]->discount_amount,
                        "final_price"=>$data['items'][0]->final_price,
                        "final_amount"=>$data['items'][0]->final_amount,
                        "cancelled_on"=> $data['cancelled_on'],
                        "paused_on"=> $data['paused_on'],
                    ]
                    ];
                    if($data['status']=='PAUSED' || $data['status']=='CANCELLED' ){
                        $body["properties"]["total_value"]=$data['total_value'];
                    }else{
                        $body["properties"]["total_value"]=$data['total_value']+$club['properties']['total_value'];
                    }
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                    ])->patch('https://api.hubapi.com/crm/v3/objects/2-15942972/'.$club['id'],$body);
                }
                $membership=$response->json();
//            //get contact
                $contact = Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->get('https://api.hubapi.com/crm/v3/objects/contacts/'.$data['email'].'?idProperty=email');
                $contact=$contact->json();

                Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->put("https://api.hubapi.com/crm/v4/objects/2-15942972/".$membership['id']."/associations/default/0-1/".$contact['id']);

                //get Deal

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.hubapi.com/crm/v3/objects/deals/search?hapikey=',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>'{
    "filterGroups": [
        {
            "filters": [
               {
          "operator": "EQ",
            "propertyName": "shopify_order_id",
            "value": "'.$data['order_id'].'"
          }
            ]
        }
    ],
    "limit": 10,
    "properties": [
        "dealname",
        "shopify_order_id",
        "id"
    ]
}',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                    ),
                ));
                $curlResponse = json_decode(curl_exec($curl));
                curl_close($curl);
                Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->put("https://api.hubapi.com/crm/v4/objects/2-15942972/".$membership['id']."/associations/default/0-3/".$curlResponse->results[0]->id);
                Cache::store('file')->put('subscription-updated-'.$data['id'],true,180);
                return response()->json(['message'=>'ok','status'=>true],200);
            }
            catch (\Exception $e){
                dd($e);
                Log::info('updating subscription error'.$e->getMessage());
            }
        }



    }
    public function sealTopicSubscriptionCancelled(Request $request){
        $data=collect(json_decode(base64_decode($request->message['data'])))->toArray();
        $cache=Cache::store('file')->get('subscription-cancelled-'.$data['id']);
        if(empty($cache) || $cache!=true){
        try{

            $response = Http::withHeaders([
                'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
            ])->get("https://api.hubapi.com/crm/v3/objects/2-15942972/".$data['id']."?properties=seal_subscription_id,total_value&archived=false&idProperty=seal_subscription_id");
            $club=$response->json();
            $status=[
                'ACTIVE'=>85863846,
                'PAUSED'=>85863847,
                'EXPIRED'=>85819956,
                'CANCELLED'=>85819957

            ];
            if(empty($club)) {
                $body=["properties"=> [
                    "seal_subscription_id"=>$data['id'],
                    "order_placed"=>$data['order_placed'],
                    "order_id"=>$data['order_id'],
                    "email"=>$data['email'],
                    "first_name"=>$data['first_name'],
                    "last_name"=>$data['last_name'],
                    "s_address1"=>$data['s_address1'],
                    "s_address2"=>$data['s_address2'],
                    "billing_interval"=>$data['billing_interval'],
                    "city"=>$data['s_city'],
                    "zip"=>$data['s_zip'],
                    "country"=>$data['s_country'],
                    "company"=>$data['s_company'],
                    "total_value"=>$data['total_value'],
                    "subscription_type"=>$data['subscription_type'],
                    "status"=>$data['status'],
                    "hs_pipeline"=>40569144,
                    "hs_pipeline_stage"=>$status[$data['status']],
                    "customer_id"=>$data['customer_id'],
                    "product_id"=>$data['items'][0]->product_id,
                    "title"=>$data['items'][0]->title,
                    "quantity"=>$data['items'][0]->quantity,
                    "total_discount"=>$data['items'][0]->total_discount,
                    "original_price"=>$data['items'][0]->original_price,
                    "original_amount"=>$data['items'][0]->original_amount,
                    "discount_value"=>$data['items'][0]->discount_value,
                    "discount_amount"=>$data['items'][0]->discount_amount,
                    "final_price"=>$data['items'][0]->final_price,
                    "final_amount"=>$data['items'][0]->final_amount,
                    "cancelled_on"=> $data['cancelled_on'],
                    "paused_on"=> $data['paused_on'],
                ]
                ];
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->post('https://api.hubapi.com/crm/v3/objects/2-15942972',$body);
            }
            else{
                $body=["properties"=> [
                    "order_placed"=>$data['order_placed'],
                    "order_id"=>$data['order_id'],
                    "email"=>$data['email'],
                    "first_name"=>$data['first_name'],
                    "last_name"=>$data['last_name'],
                    "s_address1"=>$data['s_address1'],
                    "s_address2"=>$data['s_address2'],
                    "city"=>$data['s_city'],
                    "zip"=>$data['s_zip'],
                    "country"=>$data['s_country'],
                    "company"=>$data['s_company'],
                    "billing_interval"=>$data['billing_interval'],
                    "subscription_type"=>$data['subscription_type'],
                    "status"=>$data['status'],
                    "hs_pipeline"=>40569144,
                    "hs_pipeline_stage"=>$status[$data['status']],
                    "customer_id"=>$data['customer_id'],
                    "product_id"=>$data['items'][0]->product_id,
                    "title"=>$data['items'][0]->title,
                    "quantity"=>$data['items'][0]->quantity,
                    "total_discount"=>$data['items'][0]->total_discount,
                    "original_price"=>$data['items'][0]->original_price,
                    "original_amount"=>$data['items'][0]->original_amount,
                    "discount_value"=>$data['items'][0]->discount_value,
                    "discount_amount"=>$data['items'][0]->discount_amount,
                    "final_price"=>$data['items'][0]->final_price,
                    "final_amount"=>$data['items'][0]->final_amount,
                    "cancelled_on"=> $data['cancelled_on'],
                    "paused_on"=> $data['paused_on'],
                ]
                ];
                if($data['status']=='PAUSED' || $data['status']=='CANCELLED' ){
                    $body["properties"]["total_value"]=$data['total_value'];
                }else{
                    $body["properties"]["total_value"]=$data['total_value']+$club['properties']['total_value'];
                }
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ])->patch('https://api.hubapi.com/crm/v3/objects/2-15942972/'.$club['id'],$body);
            }
            $membership=$response->json();
//            //get contact
            $contact = Http::withHeaders([
                'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
            ])->get('https://api.hubapi.com/crm/v3/objects/contacts/'.$data['email'].'?idProperty=email');
            $contact=$contact->json();

            Http::withHeaders([
                'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
            ])->put("https://api.hubapi.com/crm/v4/objects/2-15942972/".$membership['id']."/associations/default/0-1/".$contact['id']);

            //get Deal

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.hubapi.com/crm/v3/objects/deals/search?hapikey=',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
    "filterGroups": [
        {
            "filters": [
               {
          "operator": "EQ",
            "propertyName": "shopify_order_id",
            "value": "'.$data['order_id'].'"
          }
            ]
        }
    ],
    "limit": 10,
    "properties": [
        "dealname",
        "shopify_order_id",
        "id"
    ]
}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
                ),
            ));
            $curlResponse = json_decode(curl_exec($curl));
            curl_close($curl);
            Http::withHeaders([
                'Authorization' => 'Bearer pat-na1-6f7912dd-9136-42cb-aeaa-2f5ab4f9210d'
            ])->put("https://api.hubapi.com/crm/v4/objects/2-15942972/".$membership['id']."/associations/default/0-3/".$curlResponse->results[0]->id);

            Cache::store('file')->put('subscription-cancelled-'.$data['id'],true,180);
            return response()->json(['message'=>'ok','status'=>true],200);
        }catch (\Exception $e) {
            Log::info('updating subscription error' . $e->getMessage());
        }
        }
    }
}
