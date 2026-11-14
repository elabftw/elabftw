-- The audit logs table is read most recent first: without this index every read sorts the whole table.
-- InnoDB appends the primary key to secondary indexes, so this also covers the id tie-breaker.
ALTER TABLE `audit_logs`
  ADD INDEX `idx_audit_logs_created_at` (`created_at`);
