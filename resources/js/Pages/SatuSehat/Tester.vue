<script setup>
import ApiTester from '../Bpjs/Tester.vue';

defineProps({
    endpointGroups: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ApiTester :endpoint-groups="endpointGroups" system-label="SATUSEHAT" />
</template>
