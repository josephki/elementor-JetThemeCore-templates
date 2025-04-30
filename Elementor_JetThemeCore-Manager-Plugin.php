<?php
/**
 * Plugin Name: Elementor JetThemeCore Manager
 * Plugin URI: https://yourwebsite.com/
 * Description: Ermöglicht die Auswahl, ob ein Elementor-Template von JetThemeCore verwaltet werden soll oder nicht.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * Text Domain: elementor-jetthemecore-manager
 * Domain Path: /languages
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hauptklasse für das Elementor JetThemeCore Manager Plugin
 */
class Elementor_JetThemeCore_Manager {
    
    /**
     * Plugin-Instance
     */
    private static $instance = null;
    
    /**
     * Gibt die Plugin-Instance zurück
     * 
     * @return Elementor_JetThemeCore_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Plugin-Konstanten definieren
        $this->define_constants();
        
        // Plugin-Hooks initialisieren
        $this->init_hooks();
        
        // Plugin-Textdomain laden
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Plugin-Konstanten definieren
     */
    private function define_constants() {
        define('EJTM_VERSION', '1.0.0');
        define('EJTM_PLUGIN_FILE', __FILE__);
        define('EJTM_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('EJTM_PLUGIN_URL', plugin_dir_url(__FILE__));
    }
    
    /**
     * Plugin-Hooks initialisieren
     */
    private function init_hooks() {
        // Prüfen, ob Elementor und JetThemeCore aktiv sind
        add_action('admin_init', array($this, 'check_requirements'));
        
        // Meta-Box zu Elementor Templates hinzufügen
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        
        // Template-Optionen speichern
        add_action('save_post', array($this, 'save_meta_box_data'));
        
        // Filter für JetThemeCore hinzufügen
        add_filter('jet-theme-core/template-manager/get-templates', array($this, 'filter_templates'), 10, 1);
        
        // Admin-Hinweise anzeigen
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Textdomain laden
     */
    public function load_textdomain() {
        load_plugin_textdomain('elementor-jetthemecore-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Prüft, ob alle erforderlichen Plugins aktiv sind
     */
    public function check_requirements() {
        $this->elementor_active = class_exists('\Elementor\Plugin');
        $this->jetthemecore_active = class_exists('\Jet_Theme_Core\Plugin');
        
        // Plugin deaktivieren, wenn Voraussetzungen nicht erfüllt sind
        if (!$this->elementor_active || !$this->jetthemecore_active) {
            // Transient setzen für Admin-Hinweis
            set_transient('ejtm_requirements_not_met', true, 5);
        }
    }
    
    /**
     * Admin-Hinweise anzeigen
     */
    public function admin_notices() {
        // Anforderungen nicht erfüllt
        if (get_transient('ejtm_requirements_not_met')) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('Elementor JetThemeCore Manager benötigt Elementor und JetThemeCore, um zu funktionieren.', 'elementor-jetthemecore-manager'); ?></p>
            </div>
            <?php
            delete_transient('ejtm_requirements_not_met');
        }
        
        // Plugin aktiviert
        if (get_transient('ejtm_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Elementor JetThemeCore Manager wurde erfolgreich aktiviert. Sie können jetzt Elementor-Templates von der JetThemeCore-Verwaltung ausschließen.', 'elementor-jetthemecore-manager'); ?></p>
            </div>
            <?php
            delete_transient('ejtm_activated');
        }
    }
    
    /**
     * Meta-Box zu Elementor Templates hinzufügen
     */
    public function add_meta_box() {
        // Nur fortfahren, wenn Elementor und JetThemeCore aktiv sind
        if (!$this->elementor_active || !$this->jetthemecore_active) {
            return;
        }
        
        $elementor_template_types = array(
            'elementor_library',   // Elementor Templates
            'section',             // Elementor Sections
            'page',                // WordPress Pages
            'post',                // WordPress Posts
        );
        
        // Crocoblock-spezifische Post-Types hinzufügen
        $additional_types = array('jet-theme-core');
        $elementor_template_types = array_merge($elementor_template_types, $additional_types);
        
        // Custom Post Types finden, die von Elementor unterstützt werden
        if (function_exists('get_post_types_by_support')) {
            $custom_types = get_post_types_by_support('elementor');
            if (is_array($custom_types)) {
                $elementor_template_types = array_merge($elementor_template_types, $custom_types);
            }
        }
        
        // Meta-Box zu allen Elementor-kompatiblen Post-Types hinzufügen
        foreach ($elementor_template_types as $post_type) {
            add_meta_box(
                'jetthemecore_manager_metabox',
                __('JetThemeCore Verwaltung', 'elementor-jetthemecore-manager'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Meta-Box rendern
     */
    public function render_meta_box($post) {
        // Nonce für Sicherheit hinzufügen
        wp_nonce_field('jetthemecore_manager_metabox', 'jetthemecore_manager_nonce');
        
        // Aktuellen Wert abrufen
        $value = get_post_meta($post->ID, '_exclude_from_jetthemecore', true);
        ?>
        <p>
            <label>
                <input type="checkbox" name="exclude_from_jetthemecore" value="1" <?php checked($value, '1'); ?> />
                <?php _e('Von JetThemeCore ausschließen', 'elementor-jetthemecore-manager'); ?>
            </label>
        </p>
        <p class="description">
            <?php _e('Aktivieren Sie diese Option, wenn dieses Template nicht von JetThemeCore verwaltet werden soll.', 'elementor-jetthemecore-manager'); ?>
        </p>
        <?php
    }
    
    /**
     * Meta-Box-Daten speichern
     */
    public function save_meta_box_data($post_id) {
        // Prüfen, ob Nonce gesetzt ist
        if (!isset($_POST['jetthemecore_manager_nonce'])) {
            return;
        }
        
        // Verifizieren des Nonce
        if (!wp_verify_nonce($_POST['jetthemecore_manager_nonce'], 'jetthemecore_manager_metabox')) {
            return;
        }
        
        // Prüfen auf Autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Berechtigungen prüfen
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Wert speichern oder löschen
        if (isset($_POST['exclude_from_jetthemecore'])) {
            update_post_meta($post_id, '_exclude_from_jetthemecore', '1');
        } else {
            delete_post_meta($post_id, '_exclude_from_jetthemecore');
        }
    }
    
    /**
     * Templates für JetThemeCore filtern
     */
    public function filter_templates($templates) {
        if (!is_array($templates)) {
            return $templates;
        }
        
        // Gefilterte Templates
        $filtered_templates = array();
        
        foreach ($templates as $template) {
            // Prüfen, ob Template ausgeschlossen werden soll
            $post_id = isset($template['id']) ? $template['id'] : 0;
            
            if ($post_id) {
                $is_excluded = get_post_meta($post_id, '_exclude_from_jetthemecore', true);
                
                // Nur hinzufügen, wenn nicht ausgeschlossen
                if ($is_excluded !== '1') {
                    $filtered_templates[] = $template;
                }
            } else {
                // Wenn keine ID verfügbar ist, Template hinzufügen
                $filtered_templates[] = $template;
            }
        }
        
        return $filtered_templates;
    }
}

/**
 * Plugin initialisieren
 */
function elementor_jetthemecore_manager() {
    return Elementor_JetThemeCore_Manager::get_instance();
}

// Plugin starten
elementor_jetthemecore_manager();

/**
 * Bei Aktivierung des Plugins
 */
register_activation_hook(__FILE__, function() {
    set_transient('ejtm_activated', true, 5);
});

/**
 * Helfer-Funktion, um zu prüfen, ob ein Template von JetThemeCore ausgeschlossen ist
 */
function is_template_excluded_from_jetthemecore($post_id) {
    return get_post_meta($post_id, '_exclude_from_jetthemecore', true) === '1';
}