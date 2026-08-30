PRAGMA foreign_keys=off;
BEGIN TRANSACTION;

CREATE TABLE "job_cards_new" (
    "id" integer primary key autoincrement not null, 
    "garage_id" integer not null, 
    "job_number" varchar not null, 
    "customer_id" integer not null, 
    "vehicle_id" integer not null, 
    "created_by" integer not null, 
    "status" varchar check ("status" in ('scheduled', 'listed', 'working', 'complete', 'completed', 'delivered')) not null default 'listed', 
    "expected_delivery_date" date, 
    "description" text, 
    "advance_payment" numeric not null default '0', 
    "discount" numeric not null default '0', 
    "subtotal" numeric not null default '0', 
    "gst_amount" numeric not null default '0', 
    "cgst_amount" numeric not null default '0', 
    "sgst_amount" numeric not null default '0', 
    "igst_amount" numeric not null default '0', 
    "final_total" numeric not null default '0', 
    "payment_status" varchar check ("payment_status" in ('pending', 'partial', 'paid')) not null default 'pending', 
    "created_at" datetime, 
    "updated_at" datetime, 
    "priority" varchar check ("priority" in ('Low', 'Medium', 'High')) not null default 'Medium', 
    "next_service_reminder" date, 
    "vehicle_running_km" varchar, 
    "fuel_range" varchar, 
    "amount_paid" numeric not null default '0', 
    foreign key("garage_id") references "garages"("id") on delete cascade, 
    foreign key("customer_id") references "customers"("id"), 
    foreign key("vehicle_id") references "vehicles"("id"), 
    foreign key("created_by") references "users"("id")
);

INSERT INTO "job_cards_new" SELECT * FROM "job_cards";
DROP TABLE "job_cards";
ALTER TABLE "job_cards_new" RENAME TO "job_cards";
CREATE UNIQUE INDEX "job_cards_job_number_unique" on "job_cards" ("job_number");

COMMIT;
PRAGMA foreign_keys=on;
