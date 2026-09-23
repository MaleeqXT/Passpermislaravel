<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  cancellations: Array,
  balance: Object, // 👈 comes from backend via Inertia props
})

// 👇 reactive balance state (initialize from props)
const balance = ref({
  used: props.balance?.used ?? 0,
  rest: props.balance?.rest ?? 0,
})

const approve = async (id: number) => {
  try {
    const res = await axios.post(`/admin/approvels/${id}/approve`)
    const updated = res.data.cancellation

    // update cancellation status
    const index = props.cancellations.findIndex(c => c.id === id)
    if (index !== -1) {
      props.cancellations[index].status = updated.status
    }

    if (res.data.balance) {
    stats.data.balance.rest = res.data.balance.rest
    stats.data.balance.used = res.data.balance.used
  }

    alert(res.data.success)
  } catch (err) {
    console.error(err)
    alert('Cancellation approved and 1 hour added.')

  }
}

const reject = async (id: number) => {
  try {
    const res = await axios.post(`/admin/approvels/${id}/reject`)
    const updated = res.data.cancellation

    const index = props.cancellations.findIndex(c => c.id === id)
    if (index !== -1) {
      props.cancellations[index].status = updated.status
    }
    alert(res.data.success)
  } catch (err) {
    console.error(err)
    alert('Error rejecting request')
  }
}
</script>

<template>
  <div class="p-6 bg-white shadow rounded-lg">
    <h1 class="text-xl font-bold mb-4 text-gray-800">Cancellation Requests</h1>




    <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
      <thead>
        <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-600">
          <th class="px-4 py-2 border-b">Student</th>
          <th class="px-4 py-2 border-b">Reservation</th>
          <th class="px-4 py-2 border-b">Hours</th>
          <th class="px-4 py-2 border-b">Status</th>
          <th class="px-4 py-2 border-b">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="c in cancellations"
          :key="c.id"
          class="hover:bg-gray-50 transition"
        >
          <td class="px-4 py-2 border-b">{{ c.student_name }}</td>
          <td class="px-4 py-2 border-b">
            <span v-if="c.reservation_id">
              <a
                :href="`/reservations/${c.reservation_id}`"
                class="text-blue-600 hover:underline"
                target="_blank"
              >
                #{{ c.reservation_id }}
              </a>
            </span>
            <span v-else class="text-gray-500">—</span>
          </td>
          <td class="px-4 py-2 border-b">{{ c.hours_requested }}</td>
          <td class="px-4 py-2 border-b">
            <span
              :class="{
                'px-2 py-1 rounded text-xs font-medium': true,
                'bg-yellow-100 text-yellow-700': c.status === 'pending',
                'bg-green-100 text-green-700': c.status === 'approved',
                'bg-red-100 text-red-700': c.status === 'rejected',
              }"
            >
              {{ c.status.charAt(0).toUpperCase() + c.status.slice(1) }}
            </span>
          </td>
          <td class="px-4 py-2 border-b">
            <div v-if="c.status === 'pending'" class="flex gap-2">
              <button
                @click="approve(c.id)"
                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-lg text-sm transition"
              >
                Approve
              </button>
              <button
                @click="reject(c.id)"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-sm transition"
              >
                Reject
              </button>
            </div>
            <span v-else class="text-gray-400 text-sm">No actions</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
