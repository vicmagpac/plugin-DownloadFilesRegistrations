<?php

use MapasCulturais\i;

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

 $this->import('
    mc-loading
')
?>
<mc-loading :condition="processing"><?php i::_e('Aguarde, estamos processando o download dos anexos...') ?></mc-loading>
<button style="margin:10px 0px 0px 50px;" v-if="!processing" class="button button--primary" @click="download"><?php i::_e('Download anexos inscrição') ?></button>