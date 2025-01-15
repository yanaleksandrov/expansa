<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Calypte;

use Closure;
use Expansa\DatabaseLegacy\Calypte\Exceptions\ModelNotFoundException;
use Expansa\DatabaseLegacy\Calypte\Traits\ForwardCalls;
use Expansa\DatabaseLegacy\Contracts\Calypte\Model as ModelContract;
use Expansa\DatabaseLegacy\Contracts\QueryBuilder as QueryBuilderContract;
use Expansa\DatabaseLegacy\Contracts\SchemaBuilder as SchemaBuilderContract;
use Expansa\DatabaseLegacy\Abstracts\ConnectionBase;

/**
 * Used methods from [Query/Builder]:
 *
 * @method take(int $count)
 * @method get()
 */
class Builder
{
    use ForwardCalls;

    protected ?SchemaBuilderContract $schemaBuilder = null;

    protected ?QueryBuilderContract $query = null;

    public function __construct(
        protected ?ConnectionBase $connection = null,
        protected ?ModelContract  $model = null
    )
    {
        $this->schemaBuilder = $connection->getSchemaBuilder();
        $this->query         = $connection->query();

        $this->setModel($model);
    }

    public function setModel(ModelContract $model): static
    {
        $this->model = $model;
        $this->query->from($model->getTable());

        return $this;
    }

    public function getModel(): ?ModelContract
    {
        return $this->model;
    }

    public function newModelInstance($attributes = [])
    {
        $model = get_class($this->model);
        return new $model($attributes);
    }

    public function make(array $attributes = []): ?ModelContract
    {
        return $this->newModelInstance($attributes);
    }

    public function create(array $attributes = []): ModelContract
    {
        $model = $this->make($attributes);
        $model->save();
        return $model;
    }

    public function first(): ?ModelContract
    {
        if (! is_null($attributes = $this->take(1)->first())) {
            return $this->make($attributes);
        }

        return null;
    }

    public function firstOrNew(array $attributes = []): ModelContract
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        return $this->create($attributes);
    }

    public function firstOrCreate(array $attributes = []): ModelContract
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        $model = $this->create($attributes);
        $model->save();

        return $model;
    }

    public function firstOrFail(): ModelContract
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        throw (new ModelNotFoundException())->setModel(get_class($this->model));
    }

    /**
     * @param Closure $callback
     * @return ModelContract|mixed|null
     */
    public function firstOr(Closure $callback)
    {
        if (! is_null($model = $this->first())) {
            return $model;
        }

        return $callback();
    }

    public function where(...$condition): Builder
    {
        $this->query->where(...$condition);

        return $this;
    }

    /**
     * @param $method
     * @param $parameters
     * @return \Expansa\DatabaseLegacy\Query\Builder|mixed
     * @throws \Exception
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->query, $method, $parameters);
    }
}
