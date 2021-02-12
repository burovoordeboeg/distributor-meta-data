<?php
namespace BvdB\Distributor;

class Setup {

    public function register_hooks() {

        // Configurate some default Distributor options
        ( new Config() )->register_hooks();

        // Alter the_content while pushing posts
        ( new InternalConnections\PostContent() )->register_hooks();

        // Migrate post_ids in Post located in (ACF) custom meta fields
        ( new InternalConnections\PostMeta() )->register_hooks();

        // Migrate post_id's in Block located in the_content
        ( new InternalConnections\BlockMeta() )->register_hooks();
    }
}
