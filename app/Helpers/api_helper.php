<?php

function current_project_id()
{
    return service('request')->project_id ?? null;
}


function current_user_id()
{
    return service('request')->jwt_payload->id_usuario ?? null;
}
