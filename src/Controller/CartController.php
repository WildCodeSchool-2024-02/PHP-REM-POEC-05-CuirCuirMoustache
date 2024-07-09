<?php

namespace App\Controller;

use App\Model\CartManager;
use App\Model\OrderedManager;
use App\Model\OrderitemManager;
use App\Model\ProductManager;

class CartController extends AbstractController
{
    public function index(string $status = ""): string
    {
        $productManager = new ProductManager();
        $cartManager = new CartManager();
        $cart = [];
        $totalPrice = 0;
        foreach ($cartManager->getCart() as $productId => $qty) {
            $product = $productManager->selectOneById($productId);
            $cart[] = [
                'product' => $product,
                'qty' => $qty
            ];
            $totalPrice += $product['price'] * $qty;
        }


        return $this->twig->render('Cart/index.html.twig', [
            'cart' => $cart,
            'total' => $totalPrice,
            'status' => $status
        ]);
    }

    public function add(int $id, int $qty)
    {
        //ajoute au panier
        $cartManager = new CartManager();
        $cartManager->addProduct($id, $qty);

        header('Location:/cart?status=added');
    }

    public function update(int $id, int $qty)
    {
        $cartManager = new CartManager();
        $cartManager->updateProduct($id, $qty);

        header('Location:/cart?status=updated');
    }

    public function delete(int $id): void
    {
        $cartManager = new CartManager();
        $cartManager->deleteProduct($id);

        header('Location:/cart?status=deleted');
    }

    public function order(): string
    {
        $cartManager = new CartManager();
        $cart = $cartManager->getCart();
        if (count($cart) == 0) {
            header('Location:/');
        }

        $productManager = new ProductManager();

        $totalAmount = 0;
        $cartToShow = [];
        foreach ($cart as $id => $qty) {
            $product = $productManager->selectOneById($id);
            $totalAmount += $qty * $product['price'];
            $cartToShow[] = [
                'product' => $product,
                'qty' => $qty
            ];
        }

        $orderedManager = new OrderedManager();
        $orderedId = $orderedManager->createOrder(1, $totalAmount, "order");

        // moins de commandes effectuer (mais moins DRY)
        $orderitemManager = new OrderitemManager();
        foreach ($cart as $id => $qty) {
            $product = $productManager->selectOneById($id);
            $orderitemManager->addProductToOrder($orderedId, $product['id'], $qty, $product['price']);
        }


        $cartManager->clear();
        return $this->twig->render('Cart/ordered.html.twig', [
            'cart' => $cartToShow,
            'ordered_id' => $orderedId,
            'total_amount' => $totalAmount
        ]);
    }
}
