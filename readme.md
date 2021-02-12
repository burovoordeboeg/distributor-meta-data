# Distributor Custom Fields add on

## Requirements

A multisite that has sites linked through `Internal Connections`.

## Need to know
- Currently only supports Internal Connections.
- If you use ACF groups in your custom fields, please know that these are a bit tricky. Thesee values are saved in this format: `groupkey_fieldkey`. I haven't tried this yet.
- I have disabled the auto scalling of WordPress so that it won't create `lorem-scaled.jpg` files. 
- I choose to run on `dt_push_post` hook, so a I know that all attachments / media have been pushed by Distributor and I don't need to do this myself.