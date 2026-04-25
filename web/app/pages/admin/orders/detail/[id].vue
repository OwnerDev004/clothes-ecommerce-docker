<template>
    <div>
        <HeaderBreadCrumb title="Order Details">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item>Order Details</el-breadcrumb-item>
        </HeaderBreadCrumb>
        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_25%] w-full">

            <!-- Left Side -->
            <div class="min-w-0 space-y-6">

                <!-- Progressing Card -->
                <div class="shadow-sm bg-surface rounded-card p-6">
                    <!-- header status -->
                    <section class="flex justify-between">
                        <div class="space-y-5">
                            <div class="flex gap-3">
                                <h2 class="font-sans font-semibold">#0758267/90</h2>
                                <el-tag effect="light" type="success">Paid</el-tag>
                                <el-tag effect="plain" type="warning">In Progress</el-tag>
                            </div>

                            <p class="font-Lato text-muted text-sm">Order / Order Details / #0758267/90 - April 23 ,
                                2024 at
                                6:23 pm</p>
                        </div>
                        <div>
                            <BaseButton>Refund</BaseButton>
                            <BaseButton>Return</BaseButton>
                            <BaseButton type="primary">Edit Order</BaseButton>
                        </div>
                    </section>
                    <!-- Progress -->
                    <section class="mt-8">
                        <h2 class="my-8">Progress</h2>
                        <div class="grid grid-cols-5 gap-3">
                            <div class="flex flex-col items-center gap-2">
                                <el-progress class="w-full" :percentage="100" :stroke-width="12" status="success"
                                    striped striped-flow :duration="10" :show-text="false" />
                                <div class="text-center text-xs font-medium text-slate-600">Order Confirming</div>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                <el-progress class="w-full" :percentage="100" :stroke-width="12" status="success"
                                    striped striped-flow :duration="10" :show-text="false" />
                                <div class="text-center text-xs font-medium text-slate-600">Payment Pending</div>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                <el-progress class="w-full" :percentage="50" :stroke-width="12" status="warning" striped
                                    striped-flow :duration="10" :show-text="false" />
                                <div class="text-center text-xs font-medium text-slate-600 flex items-center gap-1">
                                    Processing
                                    <el-icon class="is-loading !text-warning" :size="12">
                                        <ElIconLoading />
                                    </el-icon>
                                </div>
                            </div>
                            <!-- shipping -->
                            <div class="flex flex-col items-center gap-2">
                                <el-progress class="w-full" :percentage="0" :stroke-width="12" status="success" striped
                                    striped-flow :duration="10" :show-text="false" />
                                <div class="text-center text-xs font-medium text-slate-600">Shipping</div>
                            </div>
                            <!-- Delivered -->
                            <div class="flex flex-col items-center gap-2">
                                <el-progress class="w-full" :percentage="0" :stroke-width="12" status="success" striped
                                    striped-flow :duration="10" :show-text="false" />
                                <div class="text-center text-xs font-medium text-slate-600">Delivered</div>
                            </div>
                        </div>
                    </section>
                    <!-- footer -->
                    <section class="place-self-end mt-8">
                        <BaseButton type="primary">Make As Ready To Ship</BaseButton>
                    </section>
                </div>

                <!-- Product List Card -->
                <div class="shadow-sm bg-surface rounded-card p-6 space-y-3">
                    <h2>Product</h2>
                    <hr>
                    <BaseTable :table-data="tableData">
                        <el-table-column prop="order_id" label="Product Name & Size" />
                        <el-table-column prop="created_at" label="Quantity" />
                        <el-table-column prop="customer" label="Price" />
                        <el-table-column prop="items" label="Amount" />
                    </BaseTable>
                </div>

                <!-- Order Timeline -->
                <div class="shadow-sm bg-surface rounded-card p-6 space-y-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <h2 class="text-lg font-semibold text-slate-950">Order Timeline</h2>
                            <p class="text-sm text-slate-500">
                                A clean view of the order journey from payment to delivery.
                            </p>
                        </div>

                        <el-tag effect="light" type="warning" class="!rounded-full !px-3 !py-2">
                            Pending update
                        </el-tag>
                    </div>

                    <!-- <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <el-icon class="is-loading text-amber-500" :size="14">
                                <Loading />
                            </el-icon>
                            <span class="font-medium text-slate-950">Loading...</span>
                            <span class="text-slate-500">Order timeline is updating in real time.</span>
                        </div>
                    </div> -->

                    <el-timeline>
                        <el-timeline-item v-for="step in timelineSteps" :key="step.title" :timestamp="step.time"
                            placement="top" :type="step.state === 'done'
                                ? 'success'
                                : step.state === 'current'
                                    ? 'warning'
                                    : 'info'" :color="step.state === 'done'
                                        ? '#16a34a'
                                        : step.state === 'current'
                                            ? '#f59e0b'
                                            : '#cbd5e1'">
                            <template #dot>
                                <span
                                    class="el-timeline-item__node el-timeline-item__node--large inline-flex items-center justify-center rounded-full"
                                    :class="step.state === 'done'
                                        ? '!bg-emerald-500 text-white'
                                        : step.state === 'current'
                                            ? '!bg-amber-500 text-white'
                                            : '!bg-slate-300 text-white'">
                                    <el-icon :size="12">
                                        <Loading v-if="step.state === 'current'" class="animate-spin" />
                                        <CircleCheckFilled v-else-if="step.state === 'done'" />
                                        <Clock v-else />
                                    </el-icon>
                                </span>
                            </template>

                            <div class="rounded-2xl border p-4 transition" :class="step.state === 'done'
                                ? 'border-emerald-200 bg-emerald-50/70'
                                : step.state === 'current'
                                    ? 'border-amber-200 bg-amber-50/80 shadow-sm'
                                    : 'border-slate-200 bg-white'">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="m-0 text-sm font-semibold text-slate-950">{{ step.title }}</h3>
                                        <p class="m-0 mt-1 text-sm leading-6 text-slate-600">
                                            {{ step.description }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex shrink-0 rounded-full py-1 text-[11px] font-semibold uppercase tracking-wide"
                                        :class="step.state === 'done'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : step.state === 'current'
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'bg-slate-100 text-slate-500'">
                                        {{ step.label }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span>{{ step.time }}</span>
                                    <span v-if="step.actor">- {{ step.actor }}</span>
                                </div>
                            </div>

                        </el-timeline-item>
                    </el-timeline>
                </div>


            </div>

            <!-- Right Side -->
            <div class="min-w-0 space-y-6">
                <div class="rounded-card border border-border bg-surface p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Order Summary
                            </p>
                            <h2 class="mt-2 text-lg font-semibold text-slate-950">#0758267/90</h2>
                        </div>
                        <el-tag effect="light" type="success">Paid</el-tag>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div v-for="value in order_summaries" :key="value.title"
                            class="flex items-center justify-between gap-4 border-b border-dashed border-slate-200 pb-3 last:border-b-0 last:pb-0">
                            <p class="m-0 text-sm text-slate-500">{{ value.title }}</p>
                            <p class="m-0 text-sm font-semibold text-slate-950">{{ value.amount }}</p>
                        </div>

                        <div class="flex items-center justify-between border-t border-dashed border-slate-200 pt-4">
                            <p class="m-0 text-sm font-semibold text-slate-950">Total Amount</p>
                            <p class="m-0 text-lg font-bold text-slate-950">$737.00</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-card border border-border bg-surface p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Payment Information</p>

                    <div class="mt-5 flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white shadow-sm">
                            <span class="text-sm font-bold text-slate-800">MC</span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="m-0 text-sm font-semibold text-slate-950">Master Card</h3>
                            <p class="m-0 mt-1 text-sm text-slate-500">xxxx xxxx xxxx 7812</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Transaction ID</span>
                            <span class="font-medium text-slate-950">#IDN768139059</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Card Holder</span>
                            <span class="font-medium text-slate-950">Gaston Lapierre</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-card border border-border bg-surface p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                            <span class="text-lg font-bold">GL</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Customer Details
                            </p>
                            <h3 class="m-0 mt-2 text-lg font-semibold text-slate-950">Gaston Lapierre</h3>
                            <p class="m-0 mt-1 text-sm text-slate-500">hello@dundermuffilin.com</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4 text-sm">
                        <div>
                            <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Contact Number
                            </p>
                            <p class="m-0 mt-1 font-medium text-slate-950">(723) 732-760-5760</p>
                        </div>
                        <div>
                            <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Shipping Address
                            </p>
                            <p class="m-0 mt-1 leading-6 text-slate-700">
                                Wilson's Jewelers LTD<br>
                                1344 Hershell Hollow Road,<br>
                                Tukwila, WA 98168,<br>
                                United States
                            </p>
                        </div>
                        <div>
                            <p class="m-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Billing Address
                            </p>
                            <p class="m-0 mt-1 font-medium text-slate-950">Same as shipping address</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">

import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseTable from '~/components/ui/BaseTable.vue';
import { CircleCheckFilled, Clock, Loading } from '@element-plus/icons-vue';


definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth']
})

const tableData = ref([

])

const timelineSteps = [
    {
        title: 'Order confirmed',
        description: 'The order was reviewed and approved for fulfillment.',
        time: 'Today, 09:12 AM',
        actor: 'Confirmed by Gaston Lapierre',
        label: 'Done',
        state: 'done',
    },
    {
        title: 'Payment verified',
        description: 'Card payment was captured successfully and the receipt was sent.',
        time: 'Today, 09:18 AM',
        actor: 'Using Master Card',
        label: 'Done',
        state: 'done',
    },
    {
        title: 'Packing in progress',
        description: 'The warehouse is preparing items for shipment.',
        time: 'Just now',
        actor: 'Assigned to warehouse team',
        label: 'Live',
        state: 'current',
    },
    {
        title: 'Ready for shipping',
        description: 'Shipping label will be generated after packing is complete.',
        time: 'Upcoming',
        actor: 'Auto step',
        label: 'Next',
        state: 'upcoming',
    },
    {
        title: 'Delivered',
        description: 'Final handoff to the customer once courier scans the parcel.',
        time: 'Pending',
        actor: 'Courier tracking',
        label: 'Later',
        state: 'upcoming',
    },
] as const

const order_summaries = ref([
    {
        title: 'Sub Total',
        amount: '$777.00'
    },
    {
        title: 'Discount',
        amount: '-$60.00'
    },
    {
        title: 'Delivery Charge',
        amount: '$00.00'
    }

])
</script>

<style scoped></style>
