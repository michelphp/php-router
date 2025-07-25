<?php

namespace Test\PhpDevCommunity\Controller;

use PhpDevCommunity\Attribute\Route;

class ProductController
{
    #[Route('/products', name: 'product_index', methods: ['GET'])]
    public function index(): string
    {
        return json_encode(['products' => ['Phone', 'Laptop']]);
    }

    #[Route('/products/{id}', name: 'product_update', methods: ['PUT'], options: ['whereNumber' => 'id'])]
    public function update(): string
    {
        return json_encode(['status' => 'updated']);
    }

    #[Route('/products/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function delete(): string
    {
        return json_encode(['status' => 'deleted']);
    }
}
