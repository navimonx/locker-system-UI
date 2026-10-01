import { query, queryOne, queryInsert } from '../db.js';

export async function getLockersByDepartment(department) {
  return query(
    `SELECT id, department, floor, slot, status, owner_id
     FROM Lockers
     WHERE department = ?
     ORDER BY floor ASC, slot ASC`,
    [department]
  );
}

export async function getLockersByFloor(department, floor) {
  return query(
    `SELECT id, department, floor, slot, status, owner_id
     FROM Lockers
     WHERE department = ? AND floor = ?
     ORDER BY slot ASC`,
    [department, floor]
  );
}

export async function getLockerById(lockerId) {
  return queryOne('SELECT id, department, floor, slot, status, owner_id FROM Lockers WHERE id = ?', [lockerId]);
}

export async function getDepartments() {
  return query(
    `SELECT DISTINCT department FROM Lockers ORDER BY department ASC`
  );
}

export async function updateLockerStatus(lockerId, status, ownerId = null) {
  return query(
    `UPDATE Lockers SET status = ?, owner_id = ? WHERE id = ?`,
    [status, ownerId, lockerId]
  );
}

export async function getLockerStats() {
  return queryOne(
    `SELECT
       COUNT(*) as total,
       SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
       SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
     FROM Lockers`
  );
}
