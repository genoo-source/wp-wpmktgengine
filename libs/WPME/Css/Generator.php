<?php
/**
 * This file is part of the WPMKTGENGINE plugin.
 *
 * Copyright 2016 Genoo, LLC. All rights reserved worldwide.  (web: http://www.wpmktgengine.com/)
 * GPL Version 2 Licensing:
 *  PHP code is licensed under the GNU General Public License Ver. 2 (GPL)
 *  Licensed "As-Is"; all warranties are disclaimed.
 *  HTML: http://www.gnu.org/copyleft/gpl.html
 *  Text: http://www.gnu.org/copyleft/gpl.txt
 */

namespace WPME\Css;

/**
 * Generates and manages static CSS files from dynamic plugin data.
 *
 * Form theme CSS (wpme-styles CPT) is user-edited infrequently in wp-admin.
 * Pre-generating it to an external file lets the browser cache it and
 * eliminates the large inline <style> block that was echoed in wp_head.
 */
class Generator
{
    const DIR_NAME         = 'genoo-css';
    const FORM_THEMES_FILE = 'form-themes.css';

    public static function getUploadDir()
    {
        $upload = wp_upload_dir();
        return $upload['basedir'] . '/' . self::DIR_NAME;
    }

    public static function getUploadUrl()
    {
        $upload = wp_upload_dir();
        return $upload['baseurl'] . '/' . self::DIR_NAME;
    }

    public static function ensureDir()
    {
        $dir = self::getUploadDir();
        if (!file_exists($dir)) {
            return wp_mkdir_p($dir);
        }
        return is_writable($dir);
    }

    public static function formThemesPath()
    {
        return self::getUploadDir() . '/' . self::FORM_THEMES_FILE;
    }

    public static function formThemesUrl()
    {
        return self::getUploadUrl() . '/' . self::FORM_THEMES_FILE;
    }

    public static function formThemesExists()
    {
        return file_exists(self::formThemesPath());
    }

    public static function formThemesVersion()
    {
        $path = self::formThemesPath();
        return file_exists($path) ? (string) filemtime($path) : '1';
    }

    /**
     * Generate (or refresh) the form-themes CSS file from the current
     * wpme-styles CPT content.
     */
    public static function generateFormThemes()
    {
        if (!self::ensureDir()) {
            return false;
        }

        global $WPME_STYLES;
        $savedStyles = $WPME_STYLES;
        $WPME_STYLES = '';

        $themes = new \WPMKTENGINE\RepositoryThemes();
        $css    = $themes->getAllThemesStyles();

        $WPME_STYLES = $savedStyles;

        if (empty($css)) {
            $path = self::formThemesPath();
            if (file_exists($path)) {
                @unlink($path);
            }
            return true;
        }

        return file_put_contents(self::formThemesPath(), $css) !== false;
    }

    // -------------------------------------------------------------------------
    // Per-CTA modal CSS (Track 2)
    // -------------------------------------------------------------------------

    public static function ctaCssPath($ctaId)
    {
        return self::getUploadDir() . '/cta-' . (int) $ctaId . '.css';
    }

    public static function ctaCssUrl($ctaId)
    {
        return self::getUploadUrl() . '/cta-' . (int) $ctaId . '.css';
    }

    public static function ctaCssExists($ctaId)
    {
        return file_exists(self::ctaCssPath($ctaId));
    }

    public static function ctaCssVersion($ctaId)
    {
        $path = self::ctaCssPath($ctaId);
        return file_exists($path) ? (string) filemtime($path) : '1';
    }

    /**
     * Persist the raw CSS for a CTA to its static file.
     * Only writes if the file does not yet exist — the file is invalidated
     * (deleted) by the save_post hook, so writing only on first render
     * avoids redundant I/O on every page load.
     */
    public static function writeCtaCss($ctaId, $css)
    {
        if (empty(trim($css))) {
            return false;
        }
        $path = self::ctaCssPath($ctaId);
        if (file_exists($path)) {
            return true;
        }
        if (!self::ensureDir()) {
            return false;
        }
        return file_put_contents($path, $css) !== false;
    }

    public static function deleteCtaCss($ctaId)
    {
        $path = self::ctaCssPath($ctaId);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    // -------------------------------------------------------------------------
    // Hook registration
    // -------------------------------------------------------------------------

    public static function registerHooks()
    {
        // Regenerate form-themes file after a theme style post is saved or deleted.
        add_action('save_post_wpme-styles', function () {
            self::generateFormThemes();
        }, 20);

        add_action('delete_post', function ($postId) {
            if (get_post_type($postId) === 'wpme-styles') {
                self::generateFormThemes();
            }
        }, 20);

        // Invalidate the per-CTA CSS file when a CTA post is saved.
        add_action('save_post_cta', function ($postId) {
            self::deleteCtaCss($postId);
        }, 20);

        add_action('before_delete_post', function ($postId) {
            if (get_post_type($postId) === 'cta') {
                self::deleteCtaCss($postId);
            }
        }, 20);

        add_action('wp_trash_post', function ($postId) {
            if (get_post_type($postId) === 'cta') {
                self::deleteCtaCss($postId);
            }
        }, 20);
    }
}
