<template>
    <div>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        items: any[]
    }>(), {
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
                { text: 'Order', bold: true },
                { text: 'Customer', bold: true },
                { text: 'Items', bold: true },
                { text: 'Total', bold: true },
                { text: 'Status', bold: true },

            ],
        ]
        products.forEach((p: any) => {
            tableBody.push([

                `${(p.id || 'Id')}`,
                `${(p.customer || 'Customer')}`,
                `${p.item_count || '0'}`,
                `${formatMoney(p.amount)}`,
                `${p.status || 'pending'}`,

            ])
        })

        const docDefinition = {
            content: [
                {
                    text: 'Recent checkout activity', style: 'header'
                },
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

id: number;
customer: string;
status: string;
payment_status: string;
amount: number;
item_count: number;
updated_at: string | null;