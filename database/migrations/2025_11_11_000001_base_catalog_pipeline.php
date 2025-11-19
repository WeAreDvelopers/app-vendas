<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('supplier_imports', function (Blueprint $t) {
      $t->id();
      $t->string('supplier_name');
      $t->string('source_file');
      $t->enum('source_type',['xlsx','csv','pdf']);
      $t->enum('status',['queued','processing','done','failed'])->default('queued');
      $t->json('mapping')->nullable();
      $t->unsignedInteger('total_rows')->default(0);
      $t->unsignedInteger('processed_rows')->default(0);
      $t->text('error')->nullable();
      $t->timestamps();
    });

    Schema::create('products_raw', function (Blueprint $t) {
      $t->id();
      $t->foreignId('supplier_import_id')->constrained('supplier_imports')->cascadeOnDelete();
      $t->string('sku')->nullable();
      $t->string('ean', 20)->nullable()->index();
      $t->string('name')->nullable();
      $t->string('brand')->nullable();
      $t->decimal('cost_price', 12, 2)->nullable();
      $t->decimal('sale_price', 12, 2)->nullable();
      $t->json('extra')->nullable();
      $t->enum('status',['raw','normalized','enriched','ready'])->default('raw');
      $t->timestamps();
    });

    Schema::create('products', function (Blueprint $t) {
      $t->id();
      $t->string('sku')->unique();
      $t->string('ean', 20)->nullable()->unique();
      $t->string('brand')->nullable();
      $t->string('name');
      $t->decimal('price', 12, 2)->nullable();
      $t->unsignedInteger('stock')->default(0);
      $t->json('attributes')->nullable();
      $t->enum('status',['draft','ready','published','error'])->default('draft');
      $t->timestamps();
    });

    Schema::create('product_images', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->string('path');
      $t->string('source_url')->nullable();
      $t->string('source_license')->nullable();
      $t->boolean('bg_removed')->default(false);
      $t->unsignedTinyInteger('sort')->default(1);
      $t->timestamps();
    });

    Schema::create('listings', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->string('ml_category_id')->nullable();
      $t->string('ml_item_id')->nullable()->unique();
      $t->string('title')->nullable();
      $t->decimal('price',12,2)->nullable();
      $t->unsignedInteger('stock')->default(0);
      $t->enum('condition',['new','used'])->default('new');
      $t->enum('status',['draft','ready','queued','published','paused','error'])->default('draft');
      $t->text('last_error')->nullable();
      $t->timestamps();
    });

    Schema::create('orders', function (Blueprint $t) {
      $t->id();
      $t->string('ml_order_id')->unique();
      $t->enum('status',['paid','shipped','canceled','ready_to_print'])->default('ready_to_print');
      $t->json('payload');
      $t->string('label_url')->nullable();
      $t->timestamps();
    });

    Schema::create('order_items', function (Blueprint $t) {
      $t->id();
      $t->foreignId('order_id')->constrained()->cascadeOnDelete();
      $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
      $t->string('title');
      $t->unsignedInteger('qty');
      $t->decimal('price',12,2);
      $t->timestamps();
    });

    Schema::create('print_jobs', function (Blueprint $t) {
      $t->id();
      $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
      $t->enum('type',['label','picking_list'])->default('label');
      $t->string('driver')->default('zpl');
      $t->string('payload_path')->nullable();
      $t->longText('payload_raw')->nullable();
      $t->enum('status',['queued','printing','printed','failed'])->default('queued');
      $t->unsignedTinyInteger('attempts')->default(0);
      $t->text('last_error')->nullable();
      $t->timestamps();
      $t->index(['status','type']);
    });

    Schema::create('jobs_log', function (Blueprint $t) {
      $t->id();
      $t->string('type');
      $t->string('payload_hash')->unique();
      $t->enum('status',['done','failed','skipped'])->default('done');
      $t->text('error')->nullable();
      $t->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('jobs_log');
    Schema::dropIfExists('print_jobs');
    Schema::dropIfExists('order_items');
    Schema::dropIfExists('orders');
    Schema::dropIfExists('listings');
    Schema::dropIfExists('product_images');
    Schema::dropIfExists('products');
    Schema::dropIfExists('products_raw');
    Schema::dropIfExists('supplier_imports');
  }
};
