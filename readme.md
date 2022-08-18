# Distributor Custom Fields Meta Data add-on

Push and replace related post that you save by their `post_id`'s in custom field. 
Distributor itself pushes all the origin custom fields data and this add-on will search for those posts base on their ["original ID"](https://github.com/burovoordeboeg/distributor-meta-data/blob/master/src/InternalConnections/Utilities.php) in the destination site.
When not found it [pushes](https://github.com/burovoordeboeg/distributor-meta-data/blob/master/src/InternalConnections/AbstractMeta.php#L18) the origin post to the destination site.

## Requirements

A multisite that has sites linked through `Internal Connections`.

## Usage
Define `dtmd_block_field_keys` and `dtmd_post_field_keys` which the custom fields that contain your "relationship" `post_id`'s or an Array of `post_id`'s.

### dtmd_post_field_keys and 
We have two options to handle post meta fields:
 
A. We have an `array` with only id's `[1, 23, 44]`.
B. We have an multidimensional `array` with and `id` index `['id' => 1, 'instrument' => 'fluut' ]`.

It's also possible to filter the `id` index via `dtmd_post_meta_id_index`

**Example**  
Define via PHP which ACF field_keys the add-on needs to search for by adding it to the return Array.
So for example if your attachment file field is named `press_type_file` then create this filter:

```
add_filter( 'dtmd_post_field_keys', function(){
 return [
  'press_type_file',
 ];
});
```

## Need to know
- Currently only supports Internal Connections.
- If you use ACF groups in your custom fields, please know that these are a bit tricky. Thesee values are saved in this format: `groupkey_fieldkey`. I haven't tried this yet.
- I have disabled the auto scalling of WordPress so that it won't create `lorem-scaled.jpg` files. 
- I choose to run on `dt_push_post` hook, so a I know that all attachments / media have been pushed by Distributor and I don't need to do this myself.
- Pushing a post can take some time. See my issue in Distributor https://github.com/10up/distributor/issues/719
- I have set, but please double check the Distributor settings so that it's pushing all attached attachments on both Source site and Target site.
- If you need to push non-image files, like mp4 or mp3, enable this in Distributor via it's filter `dt_allowed_media_extensions`.
