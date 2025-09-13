<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Ticket;

use App\Http\Requests\BaseRequest;

final class StoreTicketRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $subject = $this->input('subject');
        $body = $this->input('body');

        // Just to showcase how to do some basic sanitization.
        $this->merge([
            'email'   => is_string($email) ? mb_strtolower(trim($email)) : $email,
            'subject' => is_string($subject) ? trim($subject) : $subject,
            'body'    => is_string($body) ? trim($body) : $body,
        ]);
    }

    public function rules(): array
    {
        return [
            'email'   => ['required', 'string', 'email:rfc', 'max:254'],
            'subject' => ['nullable', 'string', 'max:160'],
            'body'    => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'body'    => 'message body',
        ];
    }

    public function validated($key = null, $default = null)
    {
        // Map public 'email' -> domain field 'requester_email' for downstream layers and demo purposes.
        $data = parent::validated($key, $default);

        if (is_array($data)) {
            $data['requester_email'] = $data['email'];
            unset($data['email']);
        }

        return $data;
    }
}
