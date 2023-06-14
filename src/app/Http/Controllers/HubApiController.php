<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HubApiController extends Controller
{
    public function processApi(){
        $response = Http::withHeaders([
            'Authorization' => 'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
        ])->get('https://api.hubapi.com/crm/v3/objects/contacts/example@gmail.com?idProperty=email');
        $contact=$response->json();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
        ])->get('https://api.hubapi.com/crm/v3/objects/contacts/'.$contact['id'].'/associations/0-3');
        $deal=$response->json();
        $dealId=$deal['results'][0]['id'];


        $body=["properties"=> ["membership_name"=> "platinum","deal_sku"=>2323,"membership_status"=>"active"]];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
        ])->patch('https://api.hubapi.com/crm/v3/objects/deals/'.$dealId,$body);

        $productId=1221221;
        $response=Http::withHeaders([
         'Authorization'=>'Bearer pat-na1-b3b95ea1-9278-498a-b526-2edc004a9362'
        ])->get(
            'https://api.hubapi.com/crm/v3/objects/2-14139055/'.$productId.'?properties=club_membership_id&archived=false&idProperty=club_membership_id'
        );

        dd($response->json());


    }

    public function registerWebhooks(){
//        $body=["topic"=> "subscription/created","address"=>'https://hubspotintegration-388418.uc.r.appspot.com/subscription-webhook-created'];
//        $response = Http::withHeaders([
//            'Content-Type' => 'application/json',
//            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
//        ])->post('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks',$body);

//        $body=["topic"=> "subscription/updated","address"=>'https://hubspotintegration-388418.uc.r.appspot.com/subscription-webhook-updated'];
//        $response = Http::withHeaders([
//            'Content-Type' => 'application/json',
//            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
//        ])->post('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks',$body);
//
//
//        $body=["topic"=> "subscription/cancelled","address"=>'https://hubspotintegration-388418.uc.r.appspot.com/subscription-webhook-cancelled'];
//        $response = Http::withHeaders([
//            'Content-Type' => 'application/json',
//            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
//        ])->post('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks',$body);


        // To Delete a webhook
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
        ])->delete('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks?id=3781');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Seal-Token'=>'seal_token_pw9xxpkpbq4euvzt9ut45bnm6fo8m4fc3kvp3pc4'
        ])->get('https://app.sealsubscriptions.com/shopify/merchant/api/webhooks');
    }

    public function subscriptionCreated(Request $request){
        dd($request->all());
    }
    public function subscriptionUpdated(Request $request){
        dd($request->all());
    }

    public function subscriptionCancelled(Request $request){
        dd($request->all());
    }
}
