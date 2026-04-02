// declare type array object in TS : 3types
// 1. Named Interface
// interface AdminProductList {
//     name : string,
//     desc: string,
//     price: number,
//     image: string
// } 
// export type AdminProducts = {
//     product_list: AdminProductList[], //Named Interface
//     product_list_2: { 
//         name : string,
//         desc: string,
//         price: number,
//         image: string}[], //Inline Type
//     product_list_3: Array<{ 
//         name : string,
//         desc: string,
//         price: number,
//         image: string}>, //Array Helper
// }
interface AdminProductInmages {

}
interface AdminProductList {
    id: number | string,
    sku: string,
    slug: string,
    name : string,
    desc: string,
    price: number,
    image: string
} 
export type AdminProducts = {
      product_list: 
}
export const useAdminProduct = () => {

}