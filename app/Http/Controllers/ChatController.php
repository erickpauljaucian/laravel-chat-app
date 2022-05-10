<?php

namespace App\Http\Controllers;

use App\Events\PrivateChatEvent;
use App\Events\MsgReadEvent;
use App\Http\Resources\ChatResource;
use Illuminate\Http\Request;
use App\Models\Session;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function send(Session $session, Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);

        $message = $session->messages()->create(['content' => $this->encryptText($requestContent['message'])]);

        $chat = $message->createForSend($session->id);

        $message->createForReceive($session->id, $requestContent['to_user']);

        broadcast(new PrivateChatEvent($requestContent['message'], $chat));

        return response($chat->id, 202);
    }

    private function encryptText($plaintext, $secret_key = "5fgf5HJ5g27", $cipher = "AES-128-CBC")
    {

        $key = openssl_digest($secret_key, 'SHA256', TRUE);

        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    public function chats(Session $session)
    {
        return ChatResource::collection($session->chats->where('user_id', auth()->id()));
    }

    public function read(Session $session)
    {
        $chats = $session->chats->where('read_at', null)->where('type', 0)->where('user_id', '!=', auth()->id());

        foreach ($chats as $chat) {
            $chat->update(['read_at' => Carbon::now()]);
            broadcast(new MsgReadEvent(new ChatResource($chat), $chat->session_id));
        }
    }

    public function clear(Session $session)
    {
        $session->deleteChats();
        $session->chats->count() == 0 ? $session->deleteMessages() : '';
        return response('cleared', 200);
    }
}
