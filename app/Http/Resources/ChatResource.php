<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'message' => $this->decrypt($this->message['content']),
            'id' => $this->id,
            'type' => $this->type,
            'read_at' => $this->read_at_timing($this),
            'send_at' => $this->created_at->diffForHumans()
        ];
    }

    private function decrypt($ciphertext, $secret_key = "5fgf5HJ5g27", $cipher = "AES-128-CBC")
    {

        $c = base64_decode($ciphertext);

        $key = openssl_digest($secret_key, 'SHA256', TRUE);

        $ivlen = openssl_cipher_iv_length($cipher);

        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        if (hash_equals($hmac, $calcmac))
            return $original_plaintext . "\n";
    }

    private function read_at_timing($_this) {
        $read_at = $_this->type == 0 ? $_this->read_at : null;
        return $read_at?->diffForHumans();
    }
}
