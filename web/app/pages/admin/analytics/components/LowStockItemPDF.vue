<template>
    <div>

    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        items: any[]
    }>(),
    {
        items: () => []
    })
const emit = defineEmits(['close-pdf'])

onMounted(async () => {
    try {
        const pdfMakeModule = await import('pdfmake/build/pdfmake')
        const vfsFonts = await import('pdfmake/build/vfs_fonts')
        const pdfMake = (pdfMakeModule as any).default || (pdfMakeModule as any).pdfMake || pdfMakeModule
        pdfMake.vfs = { ...(vfsFonts.default || {}) }

        const products = props.items || []

        const tableBody: any[] = [
            [
                { text: 'Product', bold: true },
                { text: 'Size', bold: true },
                { text: 'Color', bold: true },
                { text: 'Cost', bold: true },
                { text: 'Total Left', bold: true },

            ],
        ]

        products.forEach((p: any) => {
            tableBody.push([

                `${(p.product_name || 'Product')}`,
                `${p.size ?? '-'}`,
                `${p.color ?? '-'}`,
                `${formatMoney(p.sell_price)}`,
                `${p.stock_quantity ?? '-'}`,

            ])
        })

        const docDefinition = {
            content: [
                { text: 'Low Stock Products', style: 'header' },
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
});
</script>

<style scoped></style>