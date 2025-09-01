<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class PaymentWorkflowEnhancement extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Add additional fields to pembayaran table for enhanced workflow
        $fields = [
            'original_amount' => [
                'type' => 'NUMERIC',
                'constraint' => '12,2',
                'null' => true,
                'after' => 'jumlah'
            ],
            'discount_amount' => [
                'type' => 'NUMERIC', 
                'constraint' => '12,2',
                'default' => 0,
                'after' => 'original_amount'
            ],
            'payment_reference' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'bukti_bayar'
            ],
            'auto_verified' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'after' => 'verified_at'
            ],
            'features_unlocked_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after' => 'auto_verified'
            ]
        ];

        foreach ($fields as $fieldName => $fieldConfig) {
            if (!$db->fieldExists($fieldName, 'pembayaran')) {
                $this->forge->addColumn('pembayaran', [$fieldName => $fieldConfig]);
            }
        }

        // Create payment_features table to track unlocked features per user
        if (!$db->tableExists('payment_features')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'SERIAL',
                    'unsigned' => true,
                ],
                'id_user' => [
                    'type' => 'INT',
                    'null' => false,
                ],
                'id_pembayaran' => [
                    'type' => 'INT',
                    'null' => false,
                ],
                'feature_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'unlocked_at' => [
                    'type' => 'TIMESTAMP',
                    'default' => new RawSql('CURRENT_TIMESTAMP'),
                ],
                'expires_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'BOOLEAN',
                    'default' => true,
                ]
            ]);
            
            $this->forge->addKey('id', true);
            $this->forge->createTable('payment_features');
            
            // Add foreign keys
            $db->query('ALTER TABLE payment_features ADD CONSTRAINT fk_payment_features_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');
            $db->query('ALTER TABLE payment_features ADD CONSTRAINT fk_payment_features_payment FOREIGN KEY (id_pembayaran) REFERENCES pembayaran(id_pembayaran) ON DELETE CASCADE');
            
            // Add indexes
            $db->query('CREATE INDEX idx_payment_features_user ON payment_features(id_user)');
            $db->query('CREATE INDEX idx_payment_features_feature ON payment_features(feature_name)');
            $db->query('CREATE INDEX idx_payment_features_active ON payment_features(is_active)');
        }

        // Create payment_notifications table for tracking notifications
        if (!$db->tableExists('payment_notifications')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'SERIAL',
                    'unsigned' => true,
                ],
                'id_user' => [
                    'type' => 'INT',
                    'null' => false,
                ],
                'id_pembayaran' => [
                    'type' => 'INT',
                    'null' => false,
                ],
                'notification_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ],
                'message' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'sent_at' => [
                    'type' => 'TIMESTAMP',
                    'default' => new RawSql('CURRENT_TIMESTAMP'),
                ],
                'read_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
                'is_read' => [
                    'type' => 'BOOLEAN',
                    'default' => false,
                ]
            ]);
            
            $this->forge->addKey('id', true);
            $this->forge->createTable('payment_notifications');
            
            // Add foreign keys
            $db->query('ALTER TABLE payment_notifications ADD CONSTRAINT fk_payment_notifications_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');
            $db->query('ALTER TABLE payment_notifications ADD CONSTRAINT fk_payment_notifications_payment FOREIGN KEY (id_pembayaran) REFERENCES pembayaran(id_pembayaran) ON DELETE CASCADE');
            
            // Add indexes
            $db->query('CREATE INDEX idx_payment_notifications_user ON payment_notifications(id_user)');
            $db->query('CREATE INDEX idx_payment_notifications_read ON payment_notifications(is_read)');
            $db->query('CREATE INDEX idx_payment_notifications_type ON payment_notifications(notification_type)');
        }

        // Add indexes to existing tables for better performance
        $indexes = [
            'pembayaran' => [
                'idx_pembayaran_user_event' => 'id_user, event_id',
                'idx_pembayaran_verified_at' => 'verified_at',
                'idx_pembayaran_auto_verified' => 'auto_verified'
            ],
            'abstrak' => [
                'idx_abstrak_status_user' => 'status, id_user'
            ]
        ];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $indexName => $indexColumns) {
                try {
                    $db->query("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table}({$indexColumns})");
                } catch (\Exception $e) {
                    // Index might already exist, continue
                }
            }
        }

        // Create trigger for auto-unlocking features after payment verification
        $db->query("
            CREATE OR REPLACE FUNCTION unlock_presenter_features()
            RETURNS trigger AS \$\$
            BEGIN
                -- Only process if payment is being verified (status changed to 'verified')
                IF NEW.status = 'verified' AND (OLD.status IS NULL OR OLD.status != 'verified') THEN
                    -- Check if user is a presenter
                    IF EXISTS (SELECT 1 FROM users WHERE id_user = NEW.id_user AND role = 'presenter') THEN
                        -- Unlock presenter features
                        INSERT INTO payment_features (id_user, id_pembayaran, feature_name, unlocked_at)
                        VALUES 
                            (NEW.id_user, NEW.id_pembayaran, 'attendance_scanning', NEW.verified_at),
                            (NEW.id_user, NEW.id_pembayaran, 'loa_download', NEW.verified_at),
                            (NEW.id_user, NEW.id_pembayaran, 'presenter_dashboard', NEW.verified_at)
                        ON CONFLICT DO NOTHING;
                        
                        -- Update features_unlocked_at timestamp
                        UPDATE pembayaran SET features_unlocked_at = NEW.verified_at WHERE id_pembayaran = NEW.id_pembayaran;
                        
                        -- Create notification
                        INSERT INTO payment_notifications (id_user, id_pembayaran, notification_type, title, message)
                        VALUES (
                            NEW.id_user, 
                            NEW.id_pembayaran, 
                            'features_unlocked',
                            'Presenter Features Unlocked',
                            'Your payment has been verified and all presenter features are now available!'
                        );
                    END IF;
                END IF;
                
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        
        $db->query("
            DROP TRIGGER IF EXISTS trigger_unlock_presenter_features ON pembayaran;
            CREATE TRIGGER trigger_unlock_presenter_features
            AFTER UPDATE ON pembayaran
            FOR EACH ROW
            EXECUTE PROCEDURE unlock_presenter_features();
        ");

        // Create function to check if user has specific feature unlocked
        $db->query("
            CREATE OR REPLACE FUNCTION user_has_feature(user_id INT, feature_name VARCHAR)
            RETURNS boolean AS \$\$
            BEGIN
                RETURN EXISTS (
                    SELECT 1 FROM payment_features pf
                    JOIN pembayaran p ON p.id_pembayaran = pf.id_pembayaran
                    WHERE pf.id_user = user_id 
                    AND pf.feature_name = feature_name
                    AND pf.is_active = true
                    AND p.status = 'verified'
                    AND (pf.expires_at IS NULL OR pf.expires_at > NOW())
                );
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Insert default presenter features for existing verified payments
        $db->query("
            INSERT INTO payment_features (id_user, id_pembayaran, feature_name, unlocked_at)
            SELECT p.id_user, p.id_pembayaran, feature_name, p.verified_at
            FROM pembayaran p
            JOIN users u ON u.id_user = p.id_user
            CROSS JOIN (
                VALUES ('attendance_scanning'), ('loa_download'), ('presenter_dashboard')
            ) AS features(feature_name)
            WHERE p.status = 'verified' 
            AND u.role = 'presenter'
            AND p.verified_at IS NOT NULL
            ON CONFLICT DO NOTHING;
        ");

        // Update features_unlocked_at for existing verified payments
        $db->query("
            UPDATE pembayaran 
            SET features_unlocked_at = verified_at 
            WHERE status = 'verified' 
            AND verified_at IS NOT NULL 
            AND features_unlocked_at IS NULL;
        ");
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Drop triggers and functions
        $db->query('DROP TRIGGER IF EXISTS trigger_unlock_presenter_features ON pembayaran');
        $db->query('DROP FUNCTION IF EXISTS unlock_presenter_features()');
        $db->query('DROP FUNCTION IF EXISTS user_has_feature(INT, VARCHAR)');
        
        // Drop new tables
        $this->forge->dropTable('payment_notifications', true);
        $this->forge->dropTable('payment_features', true);
        
        // Drop added columns from pembayaran
        $columnsToRemove = [
            'original_amount', 'discount_amount', 'payment_reference', 
            'auto_verified', 'features_unlocked_at'
        ];
        
        foreach ($columnsToRemove as $column) {
            if ($db->fieldExists($column, 'pembayaran')) {
                try {
                    $this->forge->dropColumn('pembayaran', $column);
                } catch (\Exception $e) {
                    // Column might not exist or have dependencies
                }
            }
        }
        
        // Drop added indexes
        $indexesToDrop = [
            'idx_pembayaran_user_event',
            'idx_pembayaran_verified_at', 
            'idx_pembayaran_auto_verified',
            'idx_abstrak_status_user'
        ];
        
        foreach ($indexesToDrop as $index) {
            try {
                $db->query("DROP INDEX IF EXISTS {$index}");
            } catch (\Exception $e) {
                // Index might not exist
            }
        }
    }
}