<?php
namespace BvdB\Distributor;

class Setup {
    public function register_hooks() {
        ( new Config() )->register_hooks();

        // Alter the_content while pushing posts
        ( new InternalConnections\Content() )->register_hooks();

        // Migrate post_ids in (ACF) custom meta fields
        ( new InternalConnections\CustomFields() )->register_hooks();
        
        // Migrate post_id's in Block located in the_content
        ( new InternalConnections\BlockMeta() )->register_hooks();
    }
}
