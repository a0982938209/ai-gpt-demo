<?php
/**
 * 須填入line_secret, line_token, ChatGPT_key
 */
$HttpRequestBody = file_get_contents('php://input'); 
$HeaderSignature = $_SERVER['HTTP_X_LINE_SIGNATURE']; 

$Hash = hash_hmac('sha256', $HttpRequestBody, 'line_secret', true); 
$HashSignature = base64_encode($Hash); 
if($HashSignature != $HeaderSignature) 
{ 
    return 'hash error!'; 
} 

$DataBody=json_decode($HttpRequestBody, true); 
foreach($DataBody['events'] as $Event) 
{ 
    //當bot收到任何訊息 
    if($Event['type'] == 'message') 
    { 
        $payload = [ 
            'replyToken' => $Event['replyToken'],
            'messages' => [
                [
                    'type' => 'text',
                    'text' => call_gpt($Event['message']['text'])
                ]
            ]
        ];
        
        // 傳送訊息
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/bot/message/reply');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . 'line_token'
        ]);
        $Result = curl_exec($ch);
        curl_close($ch);
    }
}

function call_gpt($text){

    $json = '{
        "model": "text-davinci-003",
        "prompt": "'.$text.'",
        "temperature": 0.5,
        "max_tokens": 1024,
        "top_p": 1,
        "frequency_penalty": 0.0,
        "presence_penalty": 0.6
    }';
    
    $json = json_decode(preg_replace('/[\x00-\x1F]/', '', $json), true);
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . 'ChatGPT_key'
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    
    $gtp_text = json_decode( $result, true );
    return str_replace(array("\n"), '', $gtp_text['choices'][0]['text']);

}