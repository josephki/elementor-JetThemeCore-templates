<?php
/**
 * Plugin Name: Elementor JetThemeCore Fix
 * Plugin URI: https://yourwebsite.com/
 * Description: Behebt Konflikte zwischen Elementor und JetThemeCore, sodass Templates normal bearbeitet werden können.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * Text Domain: elementor-jetthemecore-fix
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hauptklasse des Plugins
 */
class Elementor_JetThemeCore_Fix {
    
    /**
     * Plugin-Instance
     */
    private static $instance = null;
    
    /**
     * Plugin-Version
     */
    const VERSION = '1.0.0';
    
    /**
     * Singleton-Pattern: Instance erhalten
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Plugin initialisieren
        add_action('plugins_loaded', array($this, 'init'));
        
        // Aktivierungshook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Admin-Hinweis
        add_action('admin_notices', array($this, 'admin_notice'));
    }
    
    /**
     * Bei Aktivierung ausführen
     */
    public function activate() {
        // Hinweis für die Aktivierung setzen
        set_transient('elementor_jetthemecore_fix_activated', true, 60);
    }
    
    /**
     * Plugin initialisieren
     */
    public function init() {
        // Prüfen, ob Elementor und JetThemeCore installiert sind
        if (!did_action('elementor/loaded') || !class_exists('Jet_Theme_Core\Plugin')) {
            add_action('admin_notices', array($this, 'missing_plugins_notice'));
            return;
        }
        
        // JetThemeCore für Elementor deaktivieren
        $this->disable_jetthemecore_in_elementor();
        
        // Template-Management
        $this->setup_template_management();
        
        // Listenansicht-Integration
        $this->setup_list_view_integration();
        
        // Admin-Tools
        $this->setup_admin_tools();
        
        // "Mit Elementor bearbeiten"-Button hinzufügen
        add_action('admin_bar_menu', array($this, 'add_edit_with_elementor_button'), 100);
        
        // JavaScript Fix für den Elementor-Editor
        add_action('admin_footer', array($this, 'add_editor_fix_script'), 999);
        
        // Übersetzungen laden
        load_plugin_textdomain('elementor-jetthemecore-fix', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * JetThemeCore in Elementor deaktivieren
     */
    private function disable_jetthemecore_in_elementor() {
        add_action('init', function() {
            // Wenn im Elementor-Bearbeitungsmodus
            $is_elementor_edit = isset($_GET['action']) && $_GET['action'] === 'elementor';
            $is_elementor_preview = isset($_GET['elementor-preview']);
            
            if ($is_elementor_edit || $is_elementor_preview) {
                // Template Manager Hooks vollständig entfernen
                if (class_exists('Jet_Theme_Core\\Template_Manager\\Manager')) {
                    $manager = Jet_Theme_Core\Template_Manager\Manager::get_instance();
                    
                    // Wichtige JetThemeCore-Hooks entfernen
                    remove_filter('template_include', array($manager, 'template_include'), 9);
                    remove_filter('elementor/documents/get/post_id', array($manager, 'set_template_id'), 10);
                    
                    // Frontend-Handler entfernen, wenn verfügbar
                    if (isset($manager->frontend)) {
                        remove_action('wp_enqueue_scripts', array($manager->frontend, 'enqueue_scripts'), 11);
                        remove_action('elementor/frontend/the_content', array($manager->frontend, 'add_theme_core_content'), 999);
                    }
                    
                    // Document Type Registrierung entfernen
                    remove_action('elementor/documents/register', array($manager, 'register_documents'));
                }
                
                // Alle JetThemeCore-Filter entfernen, die Elementor beeinflussen könnten
                global $wp_filter;
                $jet_pattern = '/Jet_Theme_Core/i';
                
                $hooks_to_check = array(
                    'template_include',
                    'elementor/editor/localize_settings',
                    'elementor/editor/after_enqueue_scripts',
                    'elementor/frontend/the_content',
                    'elementor/frontend/builder_content_data',
                    'elementor/template/include',
                    'elementor/documents/get/post_id'
                );
                
                foreach ($hooks_to_check as $hook) {
                    if (!isset($wp_filter[$hook])) continue;
                    
                    foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                        foreach ($callbacks as $key => $callback) {
                            // Prüfen, ob der Callback zu JetThemeCore gehört
                            if (is_array($callback['function']) && is_object($callback['function'][0])) {
                                $class_name = get_class($callback['function'][0]);
                                if (preg_match($jet_pattern, $class_name)) {
                                    remove_filter($hook, $callback['function'], $priority);
                                }
                            }
                        }
                    }
                }
            }
        }, 999);
        
        // JetThemeCore für ausgewählte Templates deaktivieren
        add_action('template_redirect', function() {
            global $post;
            
            if (!$post || !is_singular()) {
                return;
            }
            
            $disabled = get_post_meta($post->ID, '_elementor_jetthemecore_disabled', true);
            
            if ($disabled === '1') {
                // JetThemeCore für dieses Template deaktivieren
                add_filter('jet-theme-core/template-manager/get-templates', function($templates) use ($post) {
                    if (!is_array($templates)) {
                        return $templates;
                    }
                    
                    return array_filter($templates, function($template) use ($post) {
                        return !isset($template['id']) || $template['id'] != $post->ID;
                    });
                }, 999);
            }
        });
    }
    
    /**
     * Template-Management einrichten
     */
    private function setup_template_management() {
        // Template Ausschluss-Metabox hinzufügen
        add_action('add_meta_boxes', function() {
            // Alle relevanten Post-Types
            $post_types = array('elementor_library', 'page', 'post');
            
            foreach ($post_types as $post_type) {
                add_meta_box(
                    'elementor_jetthemecore_disable',
                    __('JetThemeCore für Elementor deaktivieren', 'elementor-jetthemecore-fix'),
                    function($post) {
                        // Nonce
                        wp_nonce_field('elementor_jetthemecore_disable', 'elementor_jetthemecore_disable_nonce');
                        
                        // Wert abrufen
                        $disabled = get_post_meta($post->ID, '_elementor_jetthemecore_disabled', true);
                        
                        // Checkbox anzeigen
                        ?>
                        <p>
                            <label>
                                <input type="checkbox" name="elementor_jetthemecore_disabled" value="1" <?php checked($disabled, '1'); ?>>
                                <?php _e('JetThemeCore für dieses Template deaktivieren', 'elementor-jetthemecore-fix'); ?>
                            </label>
                        </p>
                        <p class="description">
                            <?php _e('Aktivieren Sie diese Option, wenn Sie Probleme beim Bearbeiten des Templates mit Elementor haben.', 'elementor-jetthemecore-fix'); ?>
                        </p>
                        <?php
                    },
                    $post_type,
                    'side',
                    'high'
                );
            }
        });
        
        // Metabox-Daten speichern
        add_action('save_post', function($post_id) {
            // Nonce prüfen
            if (!isset($_POST['elementor_jetthemecore_disable_nonce']) || 
                !wp_verify_nonce($_POST['elementor_jetthemecore_disable_nonce'], 'elementor_jetthemecore_disable')) {
                return;
            }
            
            // Autosave prüfen
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            // Berechtigungen prüfen
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            
            // Wert speichern oder löschen
            if (isset($_POST['elementor_jetthemecore_disabled'])) {
                update_post_meta($post_id, '_elementor_jetthemecore_disabled', '1');
            } else {
                delete_post_meta($post_id, '_elementor_jetthemecore_disabled');
            }
        });
        
        // Elementor Daten wiederherstellen, wenn sie fehlen
        add_action('admin_init', function() {
            // Nur im Elementor Editor und wenn ein Post bearbeitet wird
            if (!isset($_GET['action']) || $_GET['action'] !== 'elementor' || !isset($_GET['post'])) {
                return;
            }
            
            $post_id = intval($_GET['post']);
            
            // Prüfen, ob Elementor-Daten fehlen
            $has_elementor = get_post_meta($post_id, '_elementor_edit_mode', true);
            
            if (empty($has_elementor)) {
                // Elementor-Bearbeitung aktivieren
                update_post_meta($post_id, '_elementor_edit_mode', 'builder');
                
                // Leere Elementor-Daten hinzufügen
                $elementor_data = get_post_meta($post_id, '_elementor_data', true);
                if (empty($elementor_data)) {
                    $initial_data = [
                        [
                            'id' => wp_rand(10000, 99999),
                            'elType' => 'section',
                            'settings' => [],
                            'elements' => [
                                [
                                    'id' => wp_rand(10000, 99999),
                                    'elType' => 'column',
                                    'settings' => [
                                        '_column_size' => 100
                                    ],
                                    'elements' => []
                                ]
                            ]
                        ]
                    ];
                    
                    update_post_meta($post_id, '_elementor_data', wp_slash(wp_json_encode($initial_data)));
                }
            }
        });
    }
    
    /**
     * Listenansicht-Integration einrichten
     */
    private function setup_list_view_integration() {
        // Benutzerdefinierte Spalte zur Template-Liste hinzufügen
        add_filter('manage_elementor_library_posts_columns', function($columns) {
            $columns['jetthemecore_status'] = __('JetThemeCore Status', 'elementor-jetthemecore-fix');
            return $columns;
        });
        
        // Inhalt der benutzerdefinierten Spalte anzeigen
        add_action('manage_elementor_library_posts_custom_column', function($column_name, $post_id) {
            if ($column_name !== 'jetthemecore_status') {
                return;
            }
            
            $disabled = get_post_meta($post_id, '_elementor_jetthemecore_disabled', true) === '1';
            
            if ($disabled) {
                echo '<span style="color: #46b450; font-weight: bold;">✓ ' . __('JetThemeCore deaktiviert', 'elementor-jetthemecore-fix') . '</span>';
            } else {
                echo '<span style="color: #dc3232;">✗ ' . __('JetThemeCore aktiv', 'elementor-jetthemecore-fix') . '</span>';
                
                // Toggle-Button hinzufügen
                ?>
                <br>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=toggle_jetthemecore_status&post_id=' . $post_id), 'toggle_jetthemecore_' . $post_id); ?>" 
                   class="button button-small" style="margin-top: 5px;">
                    <?php _e('JetThemeCore deaktivieren', 'elementor-jetthemecore-fix'); ?>
                </a>
                <?php
            }
        }, 10, 2);
        
        // AJAX-Handler für den Toggle-Button
        add_action('wp_ajax_toggle_jetthemecore_status', function() {
            // Sicherheitscheck
            $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
            
            if (!$post_id || !check_admin_referer('toggle_jetthemecore_' . $post_id)) {
                wp_die(__('Sicherheitscheck fehlgeschlagen', 'elementor-jetthemecore-fix'));
            }
            
            // Berechtigungen prüfen
            if (!current_user_can('edit_post', $post_id)) {
                wp_die(__('Keine Berechtigung', 'elementor-jetthemecore-fix'));
            }
            
            // Status umschalten
            $current_status = get_post_meta($post_id, '_elementor_jetthemecore_disabled', true);
            
            if ($current_status === '1') {
                delete_post_meta($post_id, '_elementor_jetthemecore_disabled');
            } else {
                update_post_meta($post_id, '_elementor_jetthemecore_disabled', '1');
            }
            
            // Zurück zur vorherigen Seite
            wp_redirect(wp_get_referer());
            exit;
        });
        
        // Filter-Dropdown für JetThemeCore-Status hinzufügen
        add_action('restrict_manage_posts', function($post_type) {
            if ($post_type !== 'elementor_library') {
                return;
            }
            
            $jetthemecore_status = isset($_GET['jetthemecore_status']) ? $_GET['jetthemecore_status'] : '';
            
            ?>
            <select name="jetthemecore_status" id="filter-by-jetthemecore-status">
                <option value=""><?php _e('JetThemeCore Status filtern', 'elementor-jetthemecore-fix'); ?></option>
                <option value="disabled" <?php selected($jetthemecore_status, 'disabled'); ?>>
                    <?php _e('JetThemeCore deaktiviert', 'elementor-jetthemecore-fix'); ?>
                </option>
                <option value="enabled" <?php selected($jetthemecore_status, 'enabled'); ?>>
                    <?php _e('JetThemeCore aktiv', 'elementor-jetthemecore-fix'); ?>
                </option>
            </select>
            <?php
        });
        
        // Filter für die Listenansicht anwenden
        add_filter('parse_query', function($query) {
            global $pagenow;
            
            // Nur auf der Template-Listenansicht
            if (!is_admin() || $pagenow !== 'edit.php' || 
                !isset($_GET['post_type']) || $_GET['post_type'] !== 'elementor_library') {
                return $query;
            }
            
            // JetThemeCore-Status filtern
            if (isset($_GET['jetthemecore_status']) && !empty($_GET['jetthemecore_status'])) {
                $status = $_GET['jetthemecore_status'];
                
                $meta_query = $query->get('meta_query') ? $query->get('meta_query') : [];
                
                if ($status === 'disabled') {
                    $meta_query[] = [
                        'key' => '_elementor_jetthemecore_disabled',
                        'value' => '1',
                        'compare' => '='
                    ];
                } else if ($status === 'enabled') {
                    $meta_query[] = [
                        'key' => '_elementor_jetthemecore_disabled',
                        'compare' => 'NOT EXISTS'
                    ];
                }
                
                $query->set('meta_query', $meta_query);
            }
            
            return $query;
        });
        
        // Bulk-Aktion hinzufügen
        add_filter('bulk_actions-edit-elementor_library', function($bulk_actions) {
            $bulk_actions['disable_jetthemecore'] = __('JetThemeCore deaktivieren', 'elementor-jetthemecore-fix');
            $bulk_actions['enable_jetthemecore'] = __('JetThemeCore aktivieren', 'elementor-jetthemecore-fix');
            return $bulk_actions;
        });
        
        // Bulk-Aktion ausführen
        add_filter('handle_bulk_actions-edit-elementor_library', function($redirect_to, $action_name, $post_ids) {
            if ($action_name !== 'disable_jetthemecore' && $action_name !== 'enable_jetthemecore') {
                return $redirect_to;
            }
            
            $updated = 0;
            
            foreach ($post_ids as $post_id) {
                if ($action_name === 'disable_jetthemecore') {
                    update_post_meta($post_id, '_elementor_jetthemecore_disabled', '1');
                } else {
                    delete_post_meta($post_id, '_elementor_jetthemecore_disabled');
                }
                
                $updated++;
            }
            
            return add_query_arg('bulk_jetthemecore_updated', $updated, $redirect_to);
        }, 10, 3);
        
        // Admin-Benachrichtigung nach Bulk-Aktion anzeigen
        add_action('admin_notices', function() {
            if (!empty($_REQUEST['bulk_jetthemecore_updated'])) {
                $updated_count = intval($_REQUEST['bulk_jetthemecore_updated']);
                
                printf(
                    '<div class="updated notice is-dismissible"><p>' .
                    _n(
                        '%d Template aktualisiert.',
                        '%d Templates aktualisiert.',
                        $updated_count,
                        'elementor-jetthemecore-fix'
                    ) . '</p></div>',
                    $updated_count
                );
            }
        });
    }
    
    /**
     * Admin-Tools einrichten
     */
    private function setup_admin_tools() {
        // Einstellungsseite hinzufügen
        add_action('admin_menu', function() {
            add_submenu_page(
                'tools.php',
                __('Elementor JetThemeCore Fix', 'elementor-jetthemecore-fix'),
                __('Elementor JetThemeCore', 'elementor-jetthemecore-fix'),
                'manage_options',
                'elementor-jetthemecore-fix',
                function() {
                    ?>
                    <div class="wrap">
                        <h1><?php _e('Elementor JetThemeCore Fix', 'elementor-jetthemecore-fix'); ?></h1>
                        
                        <div class="notice notice-success">
                            <p><?php _e('Das Plugin ist aktiv und behebt Konflikte zwischen Elementor und JetThemeCore.', 'elementor-jetthemecore-fix'); ?></p>
                        </div>
                        
                        <div class="card">
                            <h2><?php _e('Funktionen', 'elementor-jetthemecore-fix'); ?></h2>
                            <ul>
                                <li><?php _e('Behebt Konflikte zwischen Elementor und JetThemeCore', 'elementor-jetthemecore-fix'); ?></li>
                                <li><?php _e('Ermöglicht die normale Bearbeitung von Templates in Elementor', 'elementor-jetthemecore-fix'); ?></li>
                                <li><?php _e('Fügt eine JetThemeCore-Status-Spalte zur Template-Liste hinzu', 'elementor-jetthemecore-fix'); ?></li>
                                <li><?php _e('Bietet Massenaktionen für JetThemeCore-Deaktivierung', 'elementor-jetthemecore-fix'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="card">
                            <h2><?php _e('Statistik', 'elementor-jetthemecore-fix'); ?></h2>
                            <?php
                            $disabled_templates = $this->get_disabled_templates_count();
                            ?>
                            <p><?php printf(__('Templates mit deaktiviertem JetThemeCore: %d', 'elementor-jetthemecore-fix'), $disabled_templates); ?></p>
                        </div>
                    </div>
                    <?php
                }
            );
        });
    }
    
    /**
     * Anzahl der Templates mit deaktiviertem JetThemeCore abrufen
     */
    private function get_disabled_templates_count() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                '_elementor_jetthemecore_disabled',
                '1'
            )
        );
        
        return intval($count);
    }
    
    /**
     * "Mit Elementor bearbeiten"-Button für Frontend hinzufügen
     */
    public function add_edit_with_elementor_button($admin_bar) {
        if (!is_admin_bar_showing() || !is_singular()) {
            return;
        }
        
        global $post;
        
        if (!$post || !current_user_can('edit_post', $post->ID)) {
            return;
        }
        
        $admin_bar->add_node([
            'id' => 'edit_with_elementor_direct',
            'title' => __('Mit Elementor bearbeiten (Fix)', 'elementor-jetthemecore-fix'),
            'href' => add_query_arg([
                'post' => $post->ID,
                'action' => 'elementor',
                'jet_override' => '1'
            ], admin_url('post.php')),
            'meta' => [
                'class' => 'edit_with_elementor_direct'
            ]
        ]);
    }
    
    /**
     * JavaScript Fix für den Elementor-Editor hinzufügen
     */
    public function add_editor_fix_script() {
        // Nur im Elementor-Editor ausführen
        if (!isset($_GET['action']) || $_GET['action'] !== 'elementor') {
            return;
        }
        
        // JavaScript-Fix einfügen
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Kurze Verzögerung, um sicherzustellen, dass Elementor geladen ist
            setTimeout(function() {
                if (window.elementor) {
                    // Wenn im abgesicherten Modus, versuchen zu beheben
                    if (window.elementor.config.safe_mode) {
                        console.log('Versuche, den Elementor-Editor zu reparieren...');
                        
                        // Elementor neuladen
                        if (window.elementor.reloadPreview) {
                            window.elementor.reloadPreview();
                        }
                    }
                }
            }, 2000);
        });
        </script>
        <?php
    }
    
    /**
     * Hinweis für fehlende Plugins anzeigen
     */
    public function missing_plugins_notice() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        $message = __('Elementor JetThemeCore Fix benötigt Elementor und JetThemeCore, um zu funktionieren.', 'elementor-jetthemecore-fix');
        
        printf('<div class="notice notice-warning"><p>%s</p></div>', $message);
    }
    
    /**
     * Admin-Hinweis anzeigen
     */
    public function admin_notice() {
        if (!get_transient('elementor_jetthemecore_fix_activated')) {
            return;
        }
        
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Elementor JetThemeCore Fix wurde aktiviert und behebt nun Konflikte zwischen Elementor und JetThemeCore.', 'elementor-jetthemecore-fix'); ?></p>
        </div>
        <?php
        
        // Transient löschen
        delete_transient('elementor_jetthemecore_fix_activated');
    }
}

// Plugin initialisieren
function elementor_jetthemecore_fix() {
    return Elementor_JetThemeCore_Fix::get_instance();
}

// Plugin starten
elementor_jetthemecore_fix();