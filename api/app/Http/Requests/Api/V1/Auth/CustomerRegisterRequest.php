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
              'full_name' => 'nullable|string|max:255',
              'gender' => 'required|in:male,female',
              'email' => 'required|email|max:255|unique:customers,email',
              'user_name' => 'required|string|max:255|unique:customers,user_name',
              'phone' => 'required|string|max:20|unique:customers,phone',
              'password' => 'required|string|min:6',
          ];
      }
  }
