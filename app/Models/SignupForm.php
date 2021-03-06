<?php

namespace App\Models;

use Exception;
use Config\Services;

/**
 * Signup form
 */
class SignupForm extends \CodeIgniter\Model
{

    protected $returnType = 'array';

    protected $validationRules = [
        'username' => [
            'rules' => 'required|max_length[255]|min_length[2]',
            'label' => 'Name',
        ],
        'email' => [
            'rules' => 'required|' . UserModel::EMAIL_RULES . '|is_unique[users.email,id,{id}]',
            'label' => 'Email',
        ],
        'password' => [
            'rules' => 'required|' . UserModel::PASSWORD_RULES,
            'label' => 'Password'
        ]
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'This email address has already been taken.'
        ]
    ];

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     */
    public function signup(array $data, &$error = null)
    {
        $model = new UserModel;

        $user = new User([
            'name' => $data['username'],
            'email' => $data['email']
        ]);

        $model->setPassword($user, $data['password']);

        $user->email_verification_token = $model->generateToken();

        $return = $model->save($user);

        if (!$return)
        {
            $errors = $model->errors();

            $error = array_shift($errors);

            return false;
        }

        $id = $user->id;
        
        if (!$id)
        {
            $id = (int) $model->db->insertID();
        }

        if (!$id)
        {
            throw new Exception('User ID not defined.');
        }

        $user = $model->find($id);

        if (!$user)
        {
            throw new Exception('User not found.');
        }

        return $user;
    }

    /**
     * Sends confirmation email to user
     * @param User $user user data to with email should be send
     * @return bool whether the email was sent
     */
    public function sendEmail(User $user, &$error = null)
    {
        $model = new UserModel;

        $params = [
            'verifyLink' => $model->createEmailVerificationUrl($user)
        ];

        return $user->sendMessage('messages/signup', $params, $error);
    }

}