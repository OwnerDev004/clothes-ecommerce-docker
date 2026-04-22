export const customerStatus = {
  Active: "active",
  Inactive: "inactive",
} as const;

export type CustomerStatus = (typeof customerStatus)[keyof typeof customerStatus];

export const displayCustomerStatus: Record<string, string> = {
  [String(customerStatus.Active)]: "Active",
  [String(customerStatus.Inactive)]: "Disable Account",
};

export function getDisplayCustomerStatus(status: string): string {
  return displayCustomerStatus[String(status)] || String(status);
}
