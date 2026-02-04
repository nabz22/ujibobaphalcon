<?php

/**
 * CommerceService - Handle terintegrasi Sales, Purchase, Invoice, Inventory
 */
class CommerceService
{
    private $odooService;

    public function __construct($odooService = null)
    {
        $this->odooService = $odooService;
    }

    /**
     * Create Sales Order dengan auto deduct inventory
     */
    public function createSalesOrder($data)
    {
        try {
            $order = new SalesOrder();
            $order->order_number = $data['order_number'];
            $order->customer_name = $data['customer_name'];
            $order->order_date = $data['order_date'] ?? date('Y-m-d');
            $order->status = 'draft';
            $order->notes = $data['notes'] ?? '';

            if (!$order->save()) {
                return ['error' => 'Failed to create sales order'];
            }

            $totalAmount = 0;
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::findFirst($item['product_id']);
                    if (!$product) continue;

                    $lineItem = new SalesOrderItem();
                    $lineItem->sales_order_id = $order->id;
                    $lineItem->product_id = $item['product_id'];
                    $lineItem->quantity = $item['quantity'];
                    $lineItem->unit_price = $product->list_price;
                    $lineItem->subtotal = $item['quantity'] * $product->list_price;
                    $lineItem->save();

                    $totalAmount += $lineItem->subtotal;

                    // Deduct inventory
                    $this->logInventoryMovement(
                        $product->id,
                        'outgoing',
                        'sales_order',
                        $order->id,
                        $item['quantity']
                    );
                }
            }

            $order->total_amount = $totalAmount;
            $order->save();

            // Auto create invoice untuk Sales Order
            $invoice = $this->autoCreateInvoice($order, 'sales');

            // Sync to Odoo jika ada
            if ($this->odooService && !empty($data['sync_odoo'])) {
                $this->syncSalesOrderToOdoo($order);
            }

            return $order;
        } catch (Exception $e) {
            error_log('[CommerceService] createSalesOrder error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create Purchase Order dengan auto update inventory
     */
    public function createPurchaseOrder($data)
    {
        try {
            $order = new PurchaseOrder();
            $order->order_number = $data['order_number'];
            $order->supplier_name = $data['supplier_name'];
            $order->order_date = $data['order_date'] ?? date('Y-m-d');
            $order->status = 'draft';
            $order->notes = $data['notes'] ?? '';

            if (!$order->save()) {
                return ['error' => 'Failed to create purchase order'];
            }

            $totalAmount = 0;
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::findFirst($item['product_id']);
                    if (!$product) continue;

                    $lineItem = new PurchaseOrderItem();
                    $lineItem->purchase_order_id = $order->id;
                    $lineItem->product_id = $item['product_id'];
                    $lineItem->quantity = $item['quantity'];
                    $lineItem->unit_price = $product->cost_price;
                    $lineItem->subtotal = $item['quantity'] * $product->cost_price;
                    $lineItem->save();

                    $totalAmount += $lineItem->subtotal;

                    // Add inventory movement (will be actual stock when PO confirmed)
                    $this->logInventoryMovement(
                        $product->id,
                        'incoming',
                        'purchase_order',
                        $order->id,
                        $item['quantity'],
                        'PO created - pending receipt'
                    );
                }
            }

            $order->total_amount = $totalAmount;
            $order->save();

            // Auto create invoice untuk Purchase Order
            $invoice = $this->autoCreateInvoice($order, 'purchase');

            if ($this->odooService && !empty($data['sync_odoo'])) {
                $this->syncPurchaseOrderToOdoo($order);
            }

            return $order;
        } catch (Exception $e) {
            error_log('[CommerceService] createPurchaseOrder error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Auto create invoice from Sales/Purchase Order
     */
    private function autoCreateInvoice($order, $type = 'sales')
    {
        try {
            // Tidak buat invoice jika total 0
            if (!$order->total_amount || $order->total_amount <= 0) {
                error_log('[CommerceService] autoCreateInvoice skipped - total_amount is 0 or empty');
                return null;
            }

            // Generate invoice number
            $prefix = ($type === 'sales') ? 'INV-SO' : 'INV-PO';
            $invoiceNumber = $prefix . '-' . date('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

            // Calculate tax (default 11% PPN)
            $taxRate = 0.11;
            $taxAmount = $order->total_amount * $taxRate;

            $invoiceData = [
                'invoice_number' => $invoiceNumber,
                'invoice_date' => date('Y-m-d'),
                'total_amount' => $order->total_amount + $taxAmount,
                'tax_amount' => $taxAmount,
                'notes' => 'Auto-generated from ' . (($type === 'sales') ? 'Sales Order: ' . $order->order_number : 'Purchase Order: ' . $order->order_number)
            ];

            if ($type === 'sales') {
                $invoiceData['sales_order_id'] = $order->id;
            } else {
                $invoiceData['purchase_order_id'] = $order->id;
            }

            return $this->createInvoice($invoiceData);
        } catch (Exception $e) {
            error_log('[CommerceService] autoCreateInvoice error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create Invoice dari Sales/Purchase Order
     */
    public function createInvoice($data)
    {
        try {
            $invoice = new Invoice();
            $invoice->invoice_number = $data['invoice_number'];
            $invoice->invoice_date = $data['invoice_date'] ?? date('Y-m-d');
            $invoice->total_amount = $data['total_amount'];
            $invoice->tax_amount = $data['tax_amount'] ?? 0;
            $invoice->status = 'confirmed'; // Auto confirm - tidak draft lagi
            $invoice->notes = $data['notes'] ?? '';

            if (isset($data['sales_order_id'])) {
                $invoice->sales_order_id = $data['sales_order_id'];
            }
            if (isset($data['purchase_order_id'])) {
                $invoice->purchase_order_id = $data['purchase_order_id'];
            }

            if (!$invoice->save()) {
                return ['error' => 'Failed to create invoice'];
            }

            if ($this->odooService && !empty($data['sync_odoo'])) {
                $this->syncInvoiceToOdoo($invoice);
            }

            return $invoice;
        } catch (Exception $e) {
            error_log('[CommerceService] createInvoice error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Confirm Purchase Order dan update inventory
     */
    public function confirmPurchaseOrder($purchaseOrderId)
    {
        try {
            $order = PurchaseOrder::findFirst($purchaseOrderId);
            if (!$order) {
                return ['error' => 'Purchase order not found'];
            }

            $order->status = 'confirmed';
            $order->save();

            // Update inventory
            $items = PurchaseOrderItem::find(['purchase_order_id = ' . $purchaseOrderId]);
            foreach ($items as $item) {
                $product = Product::findFirst($item->product_id);
                if ($product) {
                    $product->quantity_on_hand = (float)$product->quantity_on_hand + (float)$item->quantity;
                    $product->save();
                    
                    // Log movement
                    $this->logInventoryMovement(
                        $product->id,
                        'incoming',
                        'purchase_order',
                        $purchaseOrderId,
                        $item->quantity,
                        'PO confirmed - stock received'
                    );
                }
            }

            return $order;
        } catch (Exception $e) {
            error_log('[CommerceService] confirmPurchaseOrder error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Log inventory movement
     */
    public function logInventoryMovement($productId, $movementType, $referenceType, $referenceId, $quantity, $notes = '')
    {
        try {
            $product = Product::findFirst($productId);
            if (!$product) return false;

            $movement = new InventoryMovement();
            $movement->product_id = $productId;
            $movement->movement_type = $movementType;
            $movement->reference_type = $referenceType;
            $movement->reference_id = $referenceId;
            $movement->quantity_before = $product->quantity_on_hand;
            $movement->quantity_moved = $quantity;
            
            if ($movementType === 'outgoing') {
                $product->quantity_on_hand = (float)$product->quantity_on_hand - (float)$quantity;
            } elseif ($movementType === 'incoming') {
                $product->quantity_on_hand = (float)$product->quantity_on_hand + (float)$quantity;
            }
            
            $movement->quantity_after = $product->quantity_on_hand;
            $movement->notes = $notes;

            $product->save();
            return $movement->save();
        } catch (Exception $e) {
            error_log('[CommerceService] logInventoryMovement error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync Sales Order to Odoo
     */
    public function syncSalesOrderToOdoo($order)
    {
        if (!$this->odooService) return null;

        try {
            $items = SalesOrderItem::find(['sales_order_id = ' . $order->id]);
            $lines = [];
            
            foreach ($items as $item) {
                $lines[] = [
                    'product_id' => [0],
                    'product_qty' => $item->quantity,
                    'price_unit' => $item->unit_price
                ];
            }

            $odooData = [
                'partner_id' => [0],
                'client_order_ref' => $order->order_number,
                'order_line' => [[0, 0, $lines]]
            ];

            return $this->odooService->create('sale.order', $odooData);
        } catch (Exception $e) {
            error_log('[CommerceService] syncSalesOrderToOdoo error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync Purchase Order to Odoo
     */
    public function syncPurchaseOrderToOdoo($order)
    {
        if (!$this->odooService) return null;

        try {
            $items = PurchaseOrderItem::find(['purchase_order_id = ' . $order->id]);
            $lines = [];
            
            foreach ($items as $item) {
                $lines[] = [
                    'product_id' => [0],
                    'product_qty' => $item->quantity,
                    'price_unit' => $item->unit_price
                ];
            }

            $odooData = [
                'partner_id' => [0],
                'origin' => $order->order_number,
                'order_line' => [[0, 0, $lines]]
            ];

            return $this->odooService->create('purchase.order', $odooData);
        } catch (Exception $e) {
            error_log('[CommerceService] syncPurchaseOrderToOdoo error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync Invoice to Odoo
     */
    public function syncInvoiceToOdoo($invoice)
    {
        if (!$this->odooService) return null;

        try {
            // Untuk Odoo 14+ gunakan 'account.move' bukan 'account.invoice'
            $odooData = [
                'name' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'move_type' => 'out_invoice', // customer invoice
                'amount_total' => $invoice->total_amount,
                'amount_tax' => $invoice->tax_amount
            ];

            $invoiceId = $this->odooService->create('account.move', $odooData);
            
            // Auto confirm invoice di Odoo (action_post)
            if ($invoiceId && is_numeric($invoiceId)) {
                $this->odooService->callAction('account.move', [$invoiceId], 'action_post');
                
                // Update odoo_invoice_id di local database
                $invoice->odoo_invoice_id = $invoiceId;
                $invoice->save();
            }
            
            return $invoiceId;
        } catch (Exception $e) {
            error_log('[CommerceService] syncInvoiceToOdoo error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get inventory movements
     */
    public function getInventoryMovements($productId = null, $limit = 100)
    {
        try {
            if ($productId) {
                return InventoryMovement::find([
                    'product_id = ' . $productId,
                    'order' => 'created_at DESC',
                    'limit' => $limit
                ]);
            } else {
                return InventoryMovement::find([
                    'order' => 'created_at DESC',
                    'limit' => $limit
                ]);
            }
        } catch (Exception $e) {
            error_log('[CommerceService] getInventoryMovements error: ' . $e->getMessage());
            return [];
        }
    }
}

// Models untuk line items
class SalesOrderItem extends \Phalcon\Mvc\Model
{
    public $id;
    public $sales_order_id;
    public $product_id;
    public $quantity;
    public $unit_price;
    public $subtotal;
    public $created_at;

    public function initialize()
    {
        $this->setSource('sales_order_items');
    }
}

class PurchaseOrderItem extends \Phalcon\Mvc\Model
{
    public $id;
    public $purchase_order_id;
    public $product_id;
    public $quantity;
    public $unit_price;
    public $subtotal;
    public $created_at;

    public function initialize()
    {
        $this->setSource('purchase_order_items');
    }
}

class InventoryMovement extends \Phalcon\Mvc\Model
{
    public $id;
    public $product_id;
    public $movement_type;
    public $reference_type;
    public $reference_id;
    public $quantity_before;
    public $quantity_after;
    public $quantity_moved;
    public $notes;
    public $created_at;

    public function initialize()
    {
        $this->setSource('inventory_movements');
    }
}
