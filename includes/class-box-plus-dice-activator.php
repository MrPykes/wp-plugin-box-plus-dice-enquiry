<?php

class Box_Plus_Dice_Activator
{
    public function activate()
    {
        // self::box_plus_dice_create_table();
        add_action('init', array($this, 'box_plus_dice_create_table'));
    }

    public function box_plus_dice_create_table()
    {
        $labels = array(
            'name'                  => _x('Forms', 'Post type general name', 'box-plus-dice'),
            'singular_name'         => _x('Form', 'Post type singular name', 'box-plus-dice'),
        );

        $args = array(
            'labels'             => $labels,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'rewrite'            => array('slug' => 'box-plus-dice-forms'),
        );

        register_post_type('box-plus-dice-forms', $args);
    }
}
$activator = new Box_Plus_Dice_Activator();
$activator->activate();
