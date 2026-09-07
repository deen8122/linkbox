<script setup>
import { onMounted, ref } from 'vue'
import Login from './components/Login.vue'
import LinkBlocks from './components/LinkBlocks.vue'
import Links from './components/Links.vue'

const user = ref(null)
const loading = ref(true)

const loadUser = async () => {
    try {
        const response = await fetch('/auth/user', {
            headers: {
                Accept: 'application/json',
            },
        })

        if (response.ok) {
            user.value = await response.json()
        }
    } finally {
        loading.value = false
    }
}

onMounted(loadUser)
</script>

<template>
    <div v-if="loading">
        Загрузка...
    </div>

    <Login v-else-if="!user" />

    <template v-else>
        <LinkBlocks />

        <Links />
    </template>
</template>
