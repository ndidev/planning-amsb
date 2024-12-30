import { createFlatStore } from "../generics/flatStore";
import type { EscaleConsignation, ShippingFilter } from "@app/types";
import { DateUtils, removeDiacritics } from "@app/utils";

/**
 * Store escales consignation.
 */
export const consignationEscales = createFlatStore<EscaleConsignation>(
  "consignation/escales",
  {
    id: null,
    navire: "TBN",
    voyage: null,
    armateur: null,
    eta_date: null,
    eta_heure: "",
    nor_date: null,
    nor_heure: "",
    pob_date: null,
    pob_heure: "",
    etb_date: null,
    etb_heure: "",
    ops_date: null,
    ops_heure: "",
    etc_date: null,
    etc_heure: "",
    etd_date: null,
    etd_heure: "",
    te_arrivee: null,
    te_depart: null,
    last_port: "",
    next_port: "",
    call_port: "Le Légué",
    quai: "",
    marchandises: [],
    commentaire: "",
  },
  {
    satisfiesParams,
  }
);

function satisfiesParams(
  call: EscaleConsignation,
  searchParams: URLSearchParams
) {
  const filter: { [P in keyof ShippingFilter]: string } =
    Object.fromEntries(searchParams);

  const startDateMatches =
    (filter.startDate ?? new DateUtils().toLocaleISODateString()) <=
    (call.etd_date ?? "9");

  const endDateMatches = (filter.endDate ?? "9") >= call.eta_date;

  const shipsMatch = filter.ships?.split(",").includes(call.navire) ?? true;

  const shipOwnersMatch =
    filter.shipOwners?.split(",").includes(call.armateur.toString()) ?? true;

  const customersMatch =
    filter.customers
      ?.split(",")
      .some((customersFilterItems) =>
        call.marchandises
          .flatMap((marchandise) => marchandise.client)
          .includes(customersFilterItems)
      ) ?? true;

  const cargoesMatch =
    filter.strictCargoes ?? false
      ? filter.cargoes
          ?.split(",")
          .some((cargoFilterItems) =>
            call.marchandises
              .flatMap((cargo) => cargo.marchandise)
              .includes(cargoFilterItems)
          ) ?? true
      : filter.cargoes
          ?.split(",")
          .some((cargoFilterItem) =>
            call.marchandises
              .flatMap((cargo) => cargo.marchandise)
              .some((cargoName) =>
                removeDiacritics(cargoName).includes(
                  removeDiacritics(cargoFilterItem)
                )
              )
          ) ?? true;

  const lastPortsMatch =
    filter.lastPorts?.split(",").includes(call.last_port) ?? true;

  const nextPortsMatch =
    filter.nextPorts?.split(",").includes(call.next_port) ?? true;

  return (
    startDateMatches &&
    endDateMatches &&
    shipsMatch &&
    shipOwnersMatch &&
    customersMatch &&
    cargoesMatch &&
    lastPortsMatch &&
    nextPortsMatch
  );
}
