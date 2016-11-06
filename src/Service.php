<?php
namespace Opine\User;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Opine\User\Model as UserModel;

class Service {
    private $root;
    private $model;
    private $jwt;
    private $activities;

    public function __construct (string $root, UserModel $model, Array $jwt, Array $activities)
    {
        $this->root = $root;
        $this->model = $model;
        $this->jwt = $jwt;
        $this->activities = $activities;
    }

    public function decodeJWT (string $token) : Array
    {
        $jwt = new Parser();
        $signer = new Sha256();
        $token = $jwt->parse($token);
        if (!$token->verify($signer, $this->jwt['signature'])) {
            return [];
        }
        $token->getHeaders();
        $token->getClaims();

        return [
            'id'    => $token->getClaim('id'),
            'email' => $token->getClaim('email'),
            'roles' => $token->getClaim('roles')
        ];
    }

    public function encodeJWT (int $id, string $email, array $roles) : string
    {
        $jwt = new Builder();
        $signer = new Sha256();

        return (string)$jwt->
            setIssuer($this->jwt['issuedBy'])->
            setAudience($this->jwt['canOnlyBeUsedBy'])->
            setId($this->jwt['identifiedBy'], true)->
            setIssuedAt(time())->
            setNotBefore(time())->
            setExpiration(time() + $this->jwt['expiresAt'])->
            set('id', $id)->
            set('email', $email)->
            set('roles', $roles)->
            sign($signer, $this->jwt['signature'])->
            getToken();
    }

    public function getRoles ($userId) : array
    {
        return $this->model->getRoles($userId);
    }

    public function getUser ($userId) : array
    {
        return $this->model->getUser($userId);
    }

    public function checkActivity () {

    }

    public function login (string $email, string $password) {
        return $this->model->login($email, $password, $this->jwt['signature']);
    }

    public function addUser ($fields) {
        return $this->model->addUser($fields);
    }
}
