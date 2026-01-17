<?php

use App\Models\CategoryTaxMapping;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('exposes category tax mappings via the expected relationship name', function () {
    $category = new TransactionCategory;

    $relation = $category->categoryTaxMappings();

    expect($relation)->toBeInstanceOf(HasMany::class)
        ->and($relation->getRelated())->toBeInstanceOf(CategoryTaxMapping::class);
});
