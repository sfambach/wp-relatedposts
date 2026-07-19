<?php
/**
 * Wird aufgerufen, wenn das Plugin in WordPress gelöscht (deinstalliert) wird.
 */

// Wenn WordPress diese Datei nicht direkt aufruft, breche sofort ab (Sicherheitsschutz)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Hier könntest du zukünftig z.B. gespeicherte Optionen aus der Datenbank löschen.
// Da unser Block aktuell keine globalen Optionen speichert, bleibt die Datei fast leer.

