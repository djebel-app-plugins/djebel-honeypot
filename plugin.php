<?php
/*
plugin_id: djebel-honeypot
plugin_name: Djebel Honeypot
description: Hidden trap fields for any form that fires app.form.action.start. A request that comes back with a trap filled ends at once with "Ok", before the rest of the site runs.
version: 1.0.0
load_priority: 1
min_php_ver: 7.4
author_name: Svetoslav Marinov (Slavi)
company_name: Orbisius
text_domain: djebel-plugin-honeypot
*/

$djebel_honeypot_obj = Djebel_Plugin_Honeypot::getInstance();
$djebel_honeypot_obj->stopFilledRequest();

Dj_App_Hooks::addAction(Djebel_Plugin_Honeypot::ACTION_FORM_START, [$djebel_honeypot_obj, 'renderFields']);

class Djebel_Plugin_Honeypot
{
    // Fired by a form right after its opening tag, so anything that adds hidden fields to forms
    // draws them there without the form naming it.
    const ACTION_FORM_START = 'app.form.action.start';

    // Named like fields a form could carry, so something automated filling every field it finds
    // fills these too; prefixed, so no real field on any site shares the name. Never like a field
    // a browser fills in by itself (an email, a phone, a name).
    const TRAP_FIELDS = [ 'djebel_plugin_honeypot_website', 'djebel_plugin_honeypot_subject', ];

    /**
     * @return Djebel_Plugin_Honeypot
     */
    public static function getInstance()
    {
        static $instance = null;

        if (is_null($instance)) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Ends a request that comes back with a trap filled, answering "Ok" as if it went through,
     * so what sent it learns nothing. Called while the plugins load, the earliest a plugin can
     * run, so nothing else of the site runs for it. One log line names the field.
     * @return bool false when the request passes
     */
    public function stopFilledRequest()
    {
        $req_obj = Dj_App_Request::getInstance();

        foreach (Djebel_Plugin_Honeypot::TRAP_FIELDS as $trap_field) {
            // Read raw, so markup the sanitizer would strip to nothing still counts as filled.
            $trap_value = $req_obj->getRaw($trap_field);

            if (empty($trap_value)) {
                continue;
            }

            $log_data = [ 'code' => 'app.plugin.honeypot.trap_filled', 'field' => $trap_field, ];
            Dj_App_Log::warn($log_data, __METHOD__);

            die('Ok');
        }

        return false;
    }

    /**
     * Draws the traps inside the form that fired the action. The hiding is an inline style so
     * it holds when no stylesheet arrives: a visible trap is filled by real people.
     * @param array $ctx
     * @return void
     */
    public function renderFields($ctx = [])
    {
        ?>
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <?php foreach (Djebel_Plugin_Honeypot::TRAP_FIELDS as $trap_field) : ?>
                <input type="text" name="<?php echo $trap_field; ?>" value="" tabindex="-1" autocomplete="off">
            <?php endforeach; ?>
        </div>
        <?php
    }
}
