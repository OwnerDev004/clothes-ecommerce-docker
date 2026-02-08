<?php
  
  namespace App\Http\Requests\Api\V1\Auth;

  use Illuminate\Foundation\Http\FormRequest;

  class CustomerLoginRequest extends FormRequest
  {
      /**
       * Determine if the user is authorized to make this request.
       */
      public function authorize(): bool
      {
          return true;
      }

      /**
       * Get the validation rules that apply to the request.
       *
       * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
       */
      public function rules(): array
      {
          return [
              'user_name' => 'required|string|exists:customers,user_name',
              'password' => 'required|string|min:6',
          ];
      }
  }
