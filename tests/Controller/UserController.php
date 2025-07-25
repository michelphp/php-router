<?php

namespace Test\PhpDevCommunity\Controller;

use PhpDevCommunity\Attribute\Route;

class UserController
{
    #[Route('/users', name: 'user_list', methods: ['GET'])]
    public function list(): string
    {
        return json_encode(['users' => ['Alice', 'Bob']]);
    }

    #[Route('/users/{id}', name: 'user_show', methods: ['GET'], options: ['whereNumber' => 'id'])]
    public function show(): string
    {
        return json_encode(['user' => 'Alice']);
    }

    #[Route('/users', name: 'user_create', methods: ['POST'])]
    public function create(): string
    {
        return json_encode(['status' => 'created']);
    }
}
