export enum customerStatus {
  Active = "active",
  Inactive = "inactive",
}

export const displayCustomerStatus: Record<customerStatus, string> = {
  [customerStatus.Active]: "Active",
  [customerStatus.Inactive]: "Disable Account",
};

export function getDisplayCustomerStatus(status: customerStatus): string {
  return displayCustomerStatus[status] || status;
}
