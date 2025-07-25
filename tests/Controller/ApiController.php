<?php

namespace Test\PhpDevCommunity\Controller;

use PhpDevCommunity\Attribute\Route;

class ApiController
{

    #[Route('/api', name: 'api_index', methods: ['GET'])]
    public function index(): string
    {
        return json_encode([
            'name' => 'John Doe',
        ]);
    }

    #[Route('/api', name: 'api_post', methods: ['POST'])]
    public function post(): string
    {
        return json_encode([
            'name' => 'John Doe',
            'status' => 'success'
        ]);
    }

}