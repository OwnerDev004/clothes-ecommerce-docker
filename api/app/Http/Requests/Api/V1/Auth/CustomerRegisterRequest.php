<?php
  
  namespace App\Http\Requests\Api\V1\Auth;

  use Illuminate\Foundation\Http\FormRequest;

  class CustomerRegisterRequest extends FormRequest
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
              'email' => 'nullable|email|unique:customers,email',
              'user_name' => 'required|unique:customers,user_name',
              'phone' => 'required|string|unique:customers,phone',
              'password' => 'required|string|min:6',
          ];
      }
  }
