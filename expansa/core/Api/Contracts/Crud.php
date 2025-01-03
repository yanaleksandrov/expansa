<?php

declare(strict_types=1);

namespace Expansa\Api\Contracts;

/**
 * Interface CrudInterface
 *
 * This interface defines the basic CRUD methods (Create, Read, Update, Delete).
 */
interface Crud
{
    /**
     * Create a new resource.
     *
     * @return array
     */
    public function create(): array;

    /**
     * Retrieve a list of resources.
     *
     * @return array
     */
    public function index(): array;

    /**
     * Update an existing resource.
     *
     * @return array
     */
    public function update(): array;

    /**
     * Delete an existing resource.
     *
     * @return array
     */
    public function delete(): array;
}
