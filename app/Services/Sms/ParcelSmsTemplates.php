<?php

namespace App\Services\Sms;

use App\Models\Parcel;
use Illuminate\Support\Carbon;

/**
 * Swahili SMS wording for parcel events.
 *
 * Template 1 ("mzigo unapotumwa") goes to the sender once the origin office
 * accepts the parcel. Template 2 ("mzigo unapopokelewa kwa maelekezo ya
 * collection") goes to the receiver when the parcel reaches the destination
 * office and has to be collected in person.
 *
 * Every placeholder is resolved from the parcel's bus / company / office
 * records and degrades to a readable fallback, so a missing office profile can
 * never produce an SMS with an empty "()" in it.
 */
class ParcelSmsTemplates
{
    private const FALLBACK_COMPANY = 'Highlink';

    /**
     * 1. MZIGO UNAPOTUMWA — to the sender, when the parcel is accepted at origin.
     */
    public static function sent(Parcel $parcel): string
    {
        $parcel->loadMissing('bus.campany.busOwnerAccount', 'bus.route', 'vender.VenderAccount');

        $company = self::companyName($parcel);
        $originOffice = self::originOffice($parcel);

        $from = self::firstFilled(
            $parcel->bus?->route?->from,
            $originOffice['location'],
            $parcel->vender?->VenderAccount?->city,
            self::companyOffice($parcel)['location'],
            'ofisi yetu'
        );

        $to = self::firstFilled(
            $parcel->bus?->route?->to,
            $parcel->receiver_delivery_address,
            self::companyOffice($parcel)['location'],
            'marudio'
        );

        $receivedAt = $parcel->created_at ? Carbon::parse($parcel->created_at) : Carbon::now();

        $receivedBy = 'umepokelewa na '.$company;
        $office = self::officeClause($originOffice['location'], $originOffice['phone']);
        if ($office !== '') {
            $receivedBy .= ', '.$office;
        }

        return self::normalise(sprintf(
            'Ndugu %s. Mzigo unaoutuma kutoka %s kwenda %s kwa %s %s. Leo tarehe %s saa %s na kupewa nambari %s. Mzigo utasafirishwa muda wowote tangu sasa. Kwa ufuatiliaji, tafadhali wasiliana nasi kupitia %s.',
            self::firstFilled($parcel->sender_name, 'mteja'),
            $from,
            $to,
            self::firstFilled($parcel->receiver_name, 'mpokeaji'),
            $receivedBy,
            $receivedAt->format('d/m/Y'),
            $receivedAt->format('H:i'),
            self::firstFilled($parcel->parcel_number, '-'),
            self::customerService($parcel)
        ));
    }

    /**
     * 2. MZIGO UNAPOPOKELEWA KWA MAELEKEZO YA COLLECTION — to the receiver.
     */
    public static function arrivedForCollection(Parcel $parcel): string
    {
        $parcel->loadMissing('bus.campany.busOwnerAccount', 'bus.route', 'vender.VenderAccount');

        $company = self::companyName($parcel);
        $companyOffice = self::companyOffice($parcel);

        $destination = self::firstFilled(
            $parcel->bus?->route?->to,
            $parcel->receiver_delivery_address,
            $companyOffice['location'],
            'marudio'
        );

        // The parcel is collected where it arrived, so the office is described by
        // the destination — never by the operator's head-office city.
        $officeLocation = $destination;
        $officePhone = self::firstFilled(
            $parcel->receiving_agent_phone,
            $companyOffice['phone'],
            $parcel->bus?->campany?->payment_number
        );

        $officeClause = self::officeClause($officeLocation, $officePhone, 'zilizopo ');
        $collectAt = 'katika ofisi za '.$company;
        if ($officeClause !== '') {
            $collectAt .= ' '.$officeClause;
        }

        return self::normalise(sprintf(
            'Ndugu %s, mzigo namba %s uliotumiwa na %s umewasili %s. Tafadhali fika %s, ukiwa na namba ya mzigo. Kwa msaada zaidi wasiliana na %s.',
            self::firstFilled($parcel->receiver_name, 'mpokeaji'),
            self::firstFilled($parcel->parcel_number, '-'),
            self::firstFilled($parcel->sender_name, 'mteja'),
            $destination,
            $collectAt,
            self::customerService($parcel)
        ));
    }

    /**
     * "ofisi ya <location>, simu <phone>" with either half optional.
     */
    private static function officeClause(?string $location, ?string $phone, string $locationPrefix = 'ofisi ya '): string
    {
        $parts = [];
        if ($location) {
            $parts[] = $locationPrefix.$location;
        }
        if ($phone) {
            $parts[] = 'simu '.$phone;
        }

        return implode(', ', $parts);
    }

    /**
     * Origin office: the vendor (agent) office that accepted the parcel when one
     * is attached, otherwise the bus owner's own office.
     *
     * @return array{location: ?string, phone: ?string}
     */
    private static function originOffice(Parcel $parcel): array
    {
        $vendor = $parcel->vender;
        if ($vendor) {
            $account = $vendor->VenderAccount;
            $location = self::address([
                self::joinHouseStreet($account?->house_number, $account?->street),
                $account?->town,
                $account?->city,
            ]);
            $phone = self::firstFilled($vendor->phone, $vendor->contact, $account?->altenative_number);

            if ($location || $phone) {
                return ['location' => $location, 'phone' => $phone];
            }
        }

        return self::companyOffice($parcel);
    }

    /**
     * Bus owner office as captured on the bus owner account profile.
     *
     * @return array{location: ?string, phone: ?string}
     */
    private static function companyOffice(Parcel $parcel): array
    {
        $account = $parcel->bus?->campany?->busOwnerAccount;

        return [
            'location' => self::address([$account?->street, $account?->town, $account?->city]),
            'phone' => self::firstFilled(
                $account?->office_number,
                $parcel->bus?->campany?->payment_number
            ),
        ];
    }

    private static function companyName(Parcel $parcel): string
    {
        return self::firstFilled($parcel->bus?->campany?->name, self::FALLBACK_COMPANY);
    }

    /**
     * Bus operator customer care ("name phone"), falling back to the office phone.
     */
    private static function customerService(Parcel $parcel): string
    {
        $bus = $parcel->bus;

        $name = self::firstFilled(
            $bus?->customer_service_name_1,
            $bus?->customer_service_name_2,
            $bus?->customer_service_name_3,
            $bus?->customer_service_name_4
        );

        $phone = self::firstFilled(
            $bus?->customer_service_contact_1,
            $bus?->customer_service_contact_2,
            $bus?->customer_service_contact_3,
            $bus?->customer_service_contact_4,
            self::companyOffice($parcel)['phone']
        );

        $service = self::join([$name, $phone]);

        return $service ?? self::companyName($parcel);
    }

    /**
     * @param  array<int, ?string>  $parts
     */
    private static function address(array $parts): ?string
    {
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '' && ! in_array($part, $clean, true)) {
                $clean[] = $part;
            }
        }

        return $clean === [] ? null : implode(', ', $clean);
    }

    private static function joinHouseStreet(?string $house, ?string $street): string
    {
        return trim(trim((string) $house).' '.trim((string) $street));
    }

    /**
     * @param  array<int, ?string>  $parts
     */
    private static function join(array $parts): ?string
    {
        $clean = array_values(array_filter(array_map(
            static fn ($part) => trim((string) $part),
            $parts
        ), static fn ($part) => $part !== ''));

        return $clean === [] ? null : implode(' ', $clean);
    }

    private static function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function normalise(string $message): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $message));
    }
}
