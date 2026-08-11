<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->nullable()->after('status');
            $table->decimal('paid_amount', 12, 2)->nullable()->after('total_amount');
            $table->string('currency', 3)->default('BRL')->after('paid_amount');
            $table->string('buyer_ml_id')->nullable()->index()->after('currency');
            $table->string('buyer_nickname')->nullable()->after('buyer_ml_id');
            $table->string('payment_status')->nullable()->after('buyer_nickname');
            $table->string('shipping_status')->nullable()->after('payment_status');
            $table->string('shipment_id')->nullable()->index()->after('shipping_status');
            $table->timestamp('date_closed')->nullable()->index()->after('shipment_id');
        });

        // orders.status: enum -> string livre (status do ML são muitos). MySQL-only DDL.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'ready_to_print'");
        }
        // SQLite: colunas enum já são varchar; nenhum ALTER necessário.
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'total_amount', 'paid_amount', 'currency', 'buyer_ml_id',
                'buyer_nickname', 'payment_status', 'shipping_status',
                'shipment_id', 'date_closed',
            ]);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('paid','shipped','canceled','ready_to_print') NOT NULL DEFAULT 'ready_to_print'");
        }
    }
};
