<script setup lang="ts">
import { Lock } from 'lucide-vue-next';

defineProps<{ until?: string }>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1 text-xs text-muted"
        :title="until ? `Editable until ${until}` : 'Locked'"
    >
        <Lock :size="12" aria-hidden="true" />
        <span>Locked</span>
    </span>
</template>
