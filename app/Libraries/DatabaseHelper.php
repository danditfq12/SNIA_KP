<?php

namespace App\Libraries;

class DatabaseHelper
{
    protected $db;
    protected $dbDriver;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->dbDriver = $this->db->getDatabase();
    }
    
    /**
     * Get the database driver type
     */
    public function getDriverName(): string
    {
        return $this->db->getDatabase()['DBDriver'] ?? 'postgre';
    }
    
    /**
     * Format date for month-year comparison
     */
    public function getMonthYearFormat(string $column, string $format = 'YYYY-MM'): string
    {
        $driver = $this->getDriverName();
        
        if ($driver === 'mysqli' || $driver === 'mysql') {
            // MySQL format
            $mysqlFormat = str_replace(['YYYY', 'MM', 'DD'], ['%Y', '%m', '%d'], $format);
            return "DATE_FORMAT($column, '$mysqlFormat')";
        } else {
            // PostgreSQL format
            return "TO_CHAR($column, '$format')";
        }
    }
    
    /**
     * Format date for day-month-year comparison
     */
    public function getDateFormat(string $column, string $format = 'YYYY-MM-DD'): string
    {
        return $this->getMonthYearFormat($column, $format);
    }
    
    /**
     * Get SERIAL or AUTO_INCREMENT syntax
     */
    public function getAutoIncrementSyntax(): string
    {
        $driver = $this->getDriverName();
        
        if ($driver === 'mysqli' || $driver === 'mysql') {
            return 'AUTO_INCREMENT';
        } else {
            return 'SERIAL';
        }
    }
    
    /**
     * Get current timestamp function
     */
    public function getCurrentTimestamp(): string
    {
        $driver = $this->getDriverName();
        
        if ($driver === 'mysqli' || $driver === 'mysql') {
            return 'NOW()';
        } else {
            return 'CURRENT_TIMESTAMP';
        }
    }
    
    /**
     * Get LIMIT syntax with offset
     */
    public function getLimitSyntax(int $limit, int $offset = 0): string
    {
        if ($offset > 0) {
            return "LIMIT $limit OFFSET $offset";
        }
        return "LIMIT $limit";
    }
    
    /**
     * Get ILIKE or LIKE for case-insensitive search
     */
    public function getCaseInsensitiveLike(): string
    {
        $driver = $this->getDriverName();
        
        if ($driver === 'mysqli' || $driver === 'mysql') {
            return 'LIKE';
        } else {
            return 'ILIKE';
        }
    }
}