<?php

namespace App\Controller\admin;

use App\Controller\AbstractController;
use App\Model\admin\ProductManager;
use App\Model\admin\CategorieManager;
use App\Model\admin\StockManager;

class ProductController extends AbstractController
{
    /**
     * List Product
     */
    public function index(): string
    {
        $productManager = new ProductManager();
        $items = $productManager->selectAllStockAndCategory();
        return $this->twig->render('Admin/Product/index.html.twig', ['items' => $items]);
    }

    /**
     * Show informations for a specific Product
     */
    public function show(int $id): string
    {
        $productManager = new ProductManager();
        $item = $productManager->selectOneById($id);
        return $this->twig->render('Admin/Product/show.html.twig', ['item' => $item]);
    }

    /**
     * Edit a specific Product
     */
    public function edit(int $id): ?string
    {
        $productManager = new ProductManager();
        $categoryManager = new CategorieManager();
        $stockManager = new StockManager();
        $categories = $categoryManager->selectAll();
        $item = $productManager->selectOneById($id);
        $stock = $stockManager->getStockById($id);
        $errors = [];
        $errorsTwo = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // clean $_POST data
            $item = array_map('trim', $_POST);
            $stock = array_map('trim', $_POST);
            $item['id'] = (int)$item['id'];
            $errors = getErrorForm($item);
            $errorsTwo = getErrorFormQuantity($item);

            // if validation is ok, update and redirection
            if (empty($errors) && empty($errorsTwo)) {
                $productManager->update($item);
                $stockManager->updateStock($stock);
                header('Location: /admin/product/show?id=' . $id);
                return null;
            }
        }

        return $this->twig->render('Admin/Product/edit.html.twig', [
            'item' => $item,
            'errors' => $errors,
            'errorsTwo' => $errorsTwo,
            'categories' => $categories,
            'stock' => $stock
        ]);
    }

    /**
     * Add a new Product
     */
    public function add(): ?string
    {
        $categoryManager = new CategorieManager();
        $categories = $categoryManager->selectAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $item = array_map('trim', $_POST);
            $errors = getErrorForm($item);
            $errorsTwo = getErrorFormQuantity($item);

            if (!empty($errors) || !empty($errorsTwo)) {
                return $this->twig->render('admin/Product/add.html.twig', [
                    'errors' => $errors,
                    'errorsTwo' => $errorsTwo,
                    'item' => $item,
                    'categories' => $categories
                ]);
            }

            $productManager = new ProductManager();

            // récupérer l'id (et la qty ?)
            $id = $productManager->insert($item);
            $qty = (int)$item['quantity'];

            // ajouter au stock ce nouveau produit
            $stockManager = new StockManager();
            $stockManager->add($id, $qty);
            return $this->twig->render('admin/Product/add.html.twig', [
                'success' => true,
                'categories' => $categories
            ]);
        }

        return $this->twig->render('admin/Product/add.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * Delete a specific Product
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $id = trim($_GET['id']);
            $categorieManager = new ProductManager();
            $categorieManager->delete((int)$id);
            header('Location:/admin/product');
        }
    }
}
function getErrorForm(array $item): array
{
    $errors = [];

    if (empty($item['name']) || strlen($item['name']) > 255) {
        $errors['name'] = 'Un nom est nécessaire et il ne doit pas dépasser 255 caractères.';
    }
    if (empty($item['description'])) {
        $errors['description'] = 'Une description est obligatoire.';
    }
    if (empty($item['price']) || !filter_var($item['price'], FILTER_VALIDATE_FLOAT) || $item['price'] < 0) {
        $errors['price'] = 'Le prix doit être un nombre valide.';
    }
    return $errors;
}
function getErrorFormQuantity(array $item): array
{
    $categoryManager = new CategorieManager();
    $errorsTwo = [];
    if (empty($item['quantity']) || !filter_var($item['quantity'], FILTER_VALIDATE_INT) || $item['quantity'] <= 0) {
        $errorsTwo['quantity'] = 'La quantité doit être supérieure ou égale à 0.';
    }
    if (empty($item['category_id']) || !$categoryManager->selectOneById((int)$item['category_id'])) {
        $errorsTwo['category_id'] = 'Category ID doit correspondre à une catégorie existante.';
    }
    return $errorsTwo;
}