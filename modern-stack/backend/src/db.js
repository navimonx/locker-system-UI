import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'locker_system',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  enableKeepAlive: true,
  keepAliveInitialDelayMs: 0
});

export async function getConnection() {
  try {
    return await pool.getConnection();
  } catch (err) {
    console.error('Database connection error:', err);
    throw err;
  }
}

export async function query(sql, params = []) {
  const conn = await getConnection();
  try {
    const [rows] = await conn.execute(sql, params);
    return rows;
  } finally {
    conn.release();
  }
}

export async function queryOne(sql, params = []) {
  const rows = await query(sql, params);
  return rows[0] || null;
}

export async function queryInsert(sql, params = []) {
  const conn = await getConnection();
  try {
    const [result] = await conn.execute(sql, params);
    return result.insertId;
  } finally {
    conn.release();
  }
}

export async function testConnection() {
  try {
    const conn = await getConnection();
    await conn.ping();
    conn.release();
    return true;
  } catch (err) {
    console.error('Database ping failed:', err);
    return false;
  }
}
