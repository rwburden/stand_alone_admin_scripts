select ti.nid,ti.tid,td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 1 and ti.tid =
td.tid and  not exists(select * from field_data_field_tags as f where f.entity_id = ti.nid and f.field_tags_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 1 and ti.tid =
td.tid and  not exists(select * from field_revision_field_tags as f where f.entity_id = ti.nid and f.field_tags_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 2 and ti.tid =
td.tid and  not exists(select * from field_data_field_content_type as f where f.entity_id = ti.nid and f.field_content_type_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 2 and ti.tid =
td.tid and  not exists(select * from field_revision_field_content_type as f where f.entity_id = ti.nid and f.field_content_type_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 3 and ti.tid =
td.tid and  not exists(select * from field_data_field_lead_item as f where f.entity_id = ti.nid and f.field_lead_item_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 3 and ti.tid =
td.tid and  not exists(select * from field_revision_field_lead_item as f where f.entity_id = ti.nid and f.field_lead_item_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 4 and ti.tid =
td.tid and  not exists(select * from field_data_field_categories as f where f.entity_id = ti.nid and f.field_categories_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 4 and ti.tid =
td.tid and  not exists(select * from field_revision_field_categories as f where f.entity_id = ti.nid and f.field_categories_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 5 and ti.tid =
td.tid and  not exists(select * from field_data_field_campaign as f where f.entity_id = ti.nid and f.field_campaign_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 5 and ti.tid =
td.tid and  not exists(select * from field_revision_field_campaign as f where f.entity_id = ti.nid and f.field_campaign_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 6 and ti.tid =
td.tid and  not exists(select * from field_data_field_authors as f where f.entity_id = ti.nid and f.field_authors_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 6 and ti.tid =
td.tid and  not exists(select * from field_revision_field_authors as f where f.entity_id = ti.nid and f.field_authors_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 7 and ti.tid =
td.tid and  not exists(select * from field_data_field_media_type as f where f.entity_id = ti.nid and f.field_media_type_tid = ti.tid);

select ti.nid,ti.tid, td.vid from taxonomy_index as ti, taxonomy_term_data as td where td.vid = 7 and ti.tid =
td.tid and  not exists(select * from field_revision_field_media_type as f where f.entity_id = ti.nid and f.field_media_type_tid = ti.tid);