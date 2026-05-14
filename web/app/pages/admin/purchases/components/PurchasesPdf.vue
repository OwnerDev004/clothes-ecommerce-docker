<template>
    <div />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'



const props = withDefaults(
    defineProps<{
        purchases_list: any[]
    }>(),
    {
        purchases_list: () => [],
    }
)
const emit = defineEmits(['close-pdf'])

onMounted(
    async () => {
    try {
        // Client-side pdf generation using pdfmake (compatible with nuxt-pdfmake)
        const pdfMakeModule = await import('pdfmake/build/pdfmake')
        const vfsFonts = await import('pdfmake/build/vfs_fonts')
        const pdfMake = (pdfMakeModule as any).default || (pdfMakeModule as any).pdfMake || pdfMakeModule
        pdfMake.vfs = { ...(vfsFonts.default || {}) }

        const purchases = props.purchases_list || []

        const tableBody: any[] = [
            [
                { text: 'Product', bold: true },
                { text: 'Qty', bold: true },
                { text: 'Stock After', bold: true },
                { text: 'Cost', bold: true },
                { text: 'Created Date', bold: true },
            ],
        ]

        purchases.forEach((p: any) => {
            tableBody.push([
                `${(p.variant?.product?.name || 'Product') + '\n' + [p.variant?.size?.name, p.variant?.color].filter(Boolean).join(' • ')}`,
                `${p.quantity ?? '-'}`,
                `${p.variant?.stock_quantity ?? '-'}`,
                `${formatMoney(p.cost_price)}`,
                `${formatAnyDate(p.created_at)}`,
            ])
        })

        const docDefinition = {
            content: [
                { text: 'Purchases List', style: 'header' },
                { text: '\n' },
                { table: { headerRows: 1, widths: ['*', 60, 60, 80, '*'], body: tableBody } },
            ],
            styles: { header: { fontSize: 18, bold: true } },
            defaultStyle: { fontSize: 11 },
        }

        pdfMake.createPdf(docDefinition).open()
    } catch (err) {
        // eslint-disable-next-line no-console
        console.error('Failed to generate PDF', err)
    } finally {
        emit('close-pdf')
    }
})
</script>

<style scoped></style>