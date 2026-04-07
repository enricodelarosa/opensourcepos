<?php

namespace Tests\Models;

use App\Models\Supplier;
use CodeIgniter\Test\CIUnitTestCase;
use stdClass;

/**
 * @internal
 */
final class SupplierTest extends CIUnitTestCase
{
    public function testGetDisplayNameAppendsSupplierRole(): void
    {
        $supplierModel = new Supplier();

        $landowner = (object) [
            'first_name' => 'Lelia',
            'last_name'  => 'Joson',
            'category'   => LAND_OWNER_SUPPLIER,
        ];
        $tenant = (object) [
            'first_name' => 'Lelia',
            'last_name'  => 'Joson',
            'category'   => TENANT_SUPPLIER,
        ];

        $this->assertSame('Lelia Joson - Land Owner', $supplierModel->getDisplayName($landowner, true));
        $this->assertSame('Lelia Joson - Tenant', $supplierModel->getDisplayName($tenant, true));
    }

    public function testGetLoanAdjustmentSuggestionsKeepsDuplicateNamesWhenRolesDiffer(): void
    {
        $supplierModel = $this->getMockBuilder(Supplier::class)
            ->onlyMethods(['get_search_suggestions', 'get_info'])
            ->getMock();

        $supplierModel->method('get_search_suggestions')
            ->willReturn([
                ['value' => 10, 'label' => 'Lelia Joson'],
                ['value' => 10, 'label' => 'Lelia Joson'],
                ['value' => 11, 'label' => 'Lelia Joson'],
            ]);

        $supplierModel->method('get_info')
            ->willReturnCallback(static function (int $personId): object {
                $supplier             = new stdClass();
                $supplier->person_id  = $personId;
                $supplier->deleted    = 0;
                $supplier->first_name = 'Lelia';
                $supplier->last_name  = 'Joson';
                $supplier->category   = $personId === 10 ? LAND_OWNER_SUPPLIER : TENANT_SUPPLIER;

                return $supplier;
            });

        $this->assertSame([
            ['value' => 10, 'label' => 'Lelia Joson - Land Owner'],
            ['value' => 11, 'label' => 'Lelia Joson - Tenant'],
        ], $supplierModel->getLoanAdjustmentSuggestions('Lelia'));
    }
}
