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
    //POST /lists
    //create list with x entries
    #[Route('/lists', name: 'create_list', methods: ['POST'])]
    public function createList(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json([
                'error' => [
                    'code'    => 'INVALID_JSON',
                    'message' => 'Request body is not valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        // name field is required
        $name = $data['name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            return $this->validationError([
                ['field' => 'name', 'message' => 'name is required and must not be empty.'],
            ], 'Field "name" is required and must not be empty.');
        }

        $itemsData = $data['items'] ?? [];
        if (!is_array($itemsData)) {
            return $this->validationError([
                ['field' => 'items', 'message' => 'items must be an array if provided.'],
            ], 'Field "items" must be an array if provided.');
        }

        $list = new ShoppingList();
        $list->setName($name);

        $allowedUnits = ['PCS', 'G', 'KG', 'ML', 'L', 'PACK'];

        foreach ($itemsData as $index => $itemData) {
            $itemValidation = $this->createOrUpdateItemFromArray(
                item: null,
                data: $itemData,
                index: $index,
                allowedUnits: $allowedUnits,
            );

            if ($itemValidation instanceof JsonResponse) {
                return $itemValidation;
            }

            /** @var Item $item */
            $item = $itemValidation;
            $item->setShoppingList($list);

            $em->persist($item);
            $list->addItem($item);
        }

        $em->persist($list);
        $em->flush();

        return $this->json(
            $this->serializeList($list),
            Response::HTTP_CREATED
        );
    }

    //POST /lists/{id}/item
    //adds new item to list and returns updated list
    #[Route('/lists/{id}/item', name: 'add_item_to_list', methods: ['POST'])]
    public function addItemToList(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $list = $em->getRepository(ShoppingList::class)->find($id);
        if (!$list) {
            return $this->notFound('Shopping list not found.');
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json([
                'error' => [
                    'code'    => 'INVALID_JSON',
                    'message' => 'Request body is not valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $allowedUnits = ['PCS', 'G', 'KG', 'ML', 'L', 'PACK'];

        $itemValidation = $this->createOrUpdateItemFromArray(
            item: null,
            data: $data,
            index: null,
            allowedUnits: $allowedUnits
        );

        if ($itemValidation instanceof JsonResponse) {
            return $itemValidation;
        }

        /** @var Item $item */
        $item = $itemValidation;
        $item->setShoppingList($list);

        $list->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($item);
        $em->flush();

        return $this->json(
            $this->serializeList($list),
            Response::HTTP_CREATED
        );
    }

    // GET /lists
    // gets all the lists
    #[Route('/lists', name: 'get_all_lists', methods: ['GET'])]
    public function getAllLists(EntityManagerInterface $em): JsonResponse
    {
        $lists = $em->getRepository(ShoppingList::class)->findAll();

        $data = [];
        foreach ($lists as $list) {
            /** @var ShoppingList $list */
            $data[] = $this->serializeList($list);
        }

        return $this->json($data);
    }

    //GET /lists/{id}/items
    // returns list with all entries
    #[Route('/lists/{id}/items', name: 'get_list_with_items', methods: ['GET'])]
    public function getListWithItems(int $id, EntityManagerInterface $em): JsonResponse
    {
        $list = $em->getRepository(ShoppingList::class)->find($id);
        if (!$list) {
            return $this->notFound('Shopping list not found.');
        }

        return $this->json($this->serializeList($list));
    }

    //GET /lists/{id}/items/{itemId}
    // returns specified item from list
    #[Route('/lists/{id}/items/{itemId}', name: 'get_item_from_list', methods: ['GET'])]
    public function getItemFromList(
        int $id,
        int $itemId,
        EntityManagerInterface $em
    ): JsonResponse {
        $list = $em->getRepository(ShoppingList::class)->find($id);
        if (!$list) {
            return $this->notFound('Shopping list not found.');
        }

        $item = $em->getRepository(Item::class)->find($itemId);
        if (!$item || $item->getShoppingList()?->getId() !== $list->getId()) {
            return $this->notFound('Item not found in this list.');
        }

        return $this->json($this->serializeItem($item));
    }

    //PUT /lists/{id}/items/{itemId}
    //updates item on a list
    #[Route('/lists/{id}/items/{itemId}', name: 'update_item', methods: ['PUT'])]
    public function updateItem(
        int $id,
        int $itemId,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $list = $em->getRepository(ShoppingList::class)->find($id);
        if (!$list) {
            return $this->notFound('Shopping list not found.');
        }

        $item = $em->getRepository(Item::class)->find($itemId);
        if (!$item || $item->getShoppingList()?->getId() !== $list->getId()) {
            return $this->notFound('Item not found in this list.');
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json([
                'error' => [
                    'code'    => 'INVALID_JSON',
                    'message' => 'Request body is not valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $allowedUnits = ['PCS', 'G', 'KG', 'ML', 'L', 'PACK'];

        $itemValidation = $this->createOrUpdateItemFromArray(
            item: $item,
            data: $data,
            index: null,
            allowedUnits: $allowedUnits
        );

        if ($itemValidation instanceof JsonResponse) {
            return $itemValidation;
        }

        $list->setUpdatedAt(new \DateTimeImmutable());
        $item->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->serializeItem($item));
    }

    //DELETE /lists/{id}
    //deletes a complete list
    #[Route('/lists/{id}', name: 'delete_list', methods: ['DELETE'])]
    public function deleteList(int $id, EntityManagerInterface $em): JsonResponse
    {
        $list = $em->getRepository(ShoppingList::class)->find($id);
        if (!$list) {
            return $this->notFound('Shopping list not found.');
        }

        $em->remove($list);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    //DELETE /lists/{id}/items/{itemId}
    //deletes one specified item from the list
    #[Route('/lists/{id}/items/{itemId}', name: 'delete_item', methods: ['DELETE'])]
    public function deleteItem(
        int $id,
        int $itemId,
        EntityManagerInterface $em
    ): JsonResponse {
        $list = $em->getRepository(ShoppingList::class)->find($id);
        if (!$list) {
            return $this->notFound('Shopping list not found.');
        }

        $item = $em->getRepository(Item::class)->find($itemId);
        if (!$item || $item->getShoppingList()?->getId() !== $list->getId()) {
            return $this->notFound('Item not found in this list.');
        }

        $em->remove($item);
        $list->setUpdatedAt(new \DateTimeImmutable());
        $item->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // --------------------------------------------------
    // Helper-Methoden
    // --------------------------------------------------

    private function serializeList(ShoppingList $list): array
    {
        $items = [];
        foreach ($list->getItems() as $item) {
            $items[] = [
                'id'         => $item->getId(),
                'name'       => $item->getName(),
                'quantity'   => $item->getQuantity() !== null ? (float) $item->getQuantity() : null,
                'unit'       => $item->getUnit(),
                'note'       => $item->getNote(),
                'is_checked' => $item->isChecked(),
                'created_at' => $item->getCreatedAt()?->format(DATE_ATOM),
                'updated_at' => $item->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'id'         => $list->getId(),
            'name'       => $list->getName(),
            'created_at' => $list->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $list->getUpdatedAt()?->format(DATE_ATOM),
            'items'      => $items,
        ];
    }

    private function serializeItem(Item $item): array
    {
        return [
            'id'         => $item->getId(),
            'list_id'    => $item->getShoppingList()?->getId(),
            'name'       => $item->getName(),
            'quantity'   => $item->getQuantity() !== null ? (float) $item->getQuantity() : null,
            'unit'       => $item->getUnit(),
            'note'       => $item->getNote(),
            'is_checked' => $item->isChecked(),
            'created_at' => $item->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $item->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function validationError(array $details, string $message): JsonResponse
    {
        return $this->json([
            'error' => [
                'code'    => 'VALIDATION_ERROR',
                'message' => $message,
                'details' => $details,
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function notFound(string $message): JsonResponse
    {
        return $this->json([
            'error' => [
                'code'    => 'NOT_FOUND',
                'message' => $message,
            ],
        ], Response::HTTP_NOT_FOUND);
    }

    //validates item data and creates/updates item object
    private function createOrUpdateItemFromArray(
        ?Item $item,
        array $data,
        ?int $index,
        array $allowedUnits
    ): Item|JsonResponse {
        $prefix = $index === null ? 'item' : "items[$index]";

        if ($item === null) {
            $item = new Item();
        }

        $name = $data['name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            return $this->validationError([
                ['field' => "$prefix.name", 'message' => 'Name des Items darf nicht leer sein.'],
            ], 'Item validation failed.');
        }

        $quantity = $data['quantity'] ?? 1;
        if (!is_numeric($quantity) || $quantity <= 0) {
            return $this->validationError([
                ['field' => "$prefix.quantity", 'message' => 'Quantity muss eine Zahl > 0 sein.'],
            ], 'Item validation failed.');
        }

        $unit = $data['unit'] ?? null;
        if (!is_string($unit) || !in_array($unit, $allowedUnits, true)) {
            return $this->validationError([
                ['field' => "$prefix.unit", 'message' => 'Unit ist ungültig. Erlaubt: ' . implode(', ', $allowedUnits)],
            ], 'Item validation failed.');
        }

        $note = $data['note'] ?? null;
        $isChecked = $data['is_checked'] ?? $item->isChecked() ?? false;

        $item->setName($name);
        $item->setQuantity(number_format((float) $quantity, 2, '.', ''));
        $item->setUnit($unit);
        $item->setNote($note);
        $item->setIsChecked((bool) $isChecked);

        return $item;
    }
}
