<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('event_type', 50)->index();
            $table->string('auditable_type', 100)->index();
            $table->unsignedInteger('auditable_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('message');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->dateTime('created_at')->useCurrent()->index();

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_lookup_idx');
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_before_update
                BEFORE UPDATE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'audit_logs is append-only and cannot be updated';
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_before_delete
                BEFORE DELETE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'audit_logs is append-only and cannot be deleted';
                END
            SQL);
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_before_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_before_delete');
        }

        Schema::dropIfExists('audit_logs');
    }
};
