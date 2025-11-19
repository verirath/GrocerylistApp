<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\ShoppingList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShoppingListController extends AbstractController
{
    #[Route('/lists', name: 'create_list', methods: ['POST'])]
    public function createList(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'error' => [
                    'code' => 'INVALID_JSON',
                    'message' => 'Request body is not valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        // name is a required field
        $name = $data['name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            return $this->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Field "name" is required and must not be empty.',
                    'details' => [
                        ['field' => 'name', 'message' => 'Name darf nicht leer sein.'],
                    ],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // get items
        $itemsData = $data['items'] ?? [];
        if (!is_array($itemsData)) {
            return $this->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Field "items" must be an array if provided.',
                    'details' => [
                        ['field' => 'items', 'message' => '"items" muss ein Array sein.'],
                    ],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // create list
        $list = new ShoppingList();
        $list->setName($name);

        $allowedUnits = ['PCS', 'G', 'KG', 'ML', 'L', 'PACK'];

        // add items
        foreach ($itemsData as $index => $itemData) {
            if (!is_array($itemData)) {
                return $this->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Each item must be an object.',
                        'details' => [
                            [
                                'field' => "items[$index]",
                                'message' => 'Item muss ein Objekt sein.',
                            ],
                        ],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $itemName = $itemData['name'] ?? null;
            if (!is_string($itemName) || trim($itemName) === '') {
                return $this->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Item validation failed.',
                        'details' => [
                            [
                                'field' => "items[$index].name",
                                'message' => 'Name des Items darf nicht leer sein.',
                            ],
                        ],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $quantity = $itemData['quantity'] ?? 1;
            if (!is_numeric($quantity) || $quantity <= 0) {
                return $this->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Item validation failed.',
                        'details' => [
                            [
                                'field' => "items[$index].quantity",
                                'message' => 'Quantity muss eine Zahl > 0 sein.',
                            ],
                        ],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $unit = $itemData['unit'] ?? null;
            if ($unit !== null && !in_array($unit, $allowedUnits, true)) {
                return $this->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Item validation failed.',
                        'details' => [
                            [
                                'field' => "items[$index].unit",
                                'message' => 'Unit ist ungültig. Erlaubt: ' . implode(', ', $allowedUnits),
                            ],
                        ],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $note      = $itemData['note'] ?? null;

            $item = new Item();
            $item->setName($itemName);
            $item->setQuantity(number_format((float)$quantity, 2, '.', ''));
            $item->setUnit($unit);
            $item->setNote($note);
            $item->setIsChecked(false); // laut Modell default false
            $item->setShoppingList($list);

            $em->persist($item);
            $list->addItem($item);
        }

        // save list
        $em->persist($list);
        $em->flush();

        // json response body
        $itemsResponse = [];
        foreach ($list->getItems() as $item) {
            $itemsResponse[] = [
                'id'         => $item->getId(),
                'name'       => $item->getName(),
                'quantity'   => $item->getQuantity() !== null ? (float)$item->getQuantity() : null,
                'unit'       => $item->getUnit(),
                'note'       => $item->getNote(),
                'is_checked' => $item->isChecked(),
                'created_at' => $item->getCreatedAt()->format(DATE_ATOM),
            ];
        }

        $responseData = [
            'id'         => $list->getId(),
            'name'       => $list->getName(),
            'created_at' => $list->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $list->getUpdatedAt()?->format(DATE_ATOM),
            'items'      => $itemsResponse,
        ];

        return $this->json($responseData, Response::HTTP_CREATED);
    }
}
