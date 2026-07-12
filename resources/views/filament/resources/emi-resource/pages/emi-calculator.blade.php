<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Side: Loan Input Form in a Card -->
        <div class="bg-white shadow-lg rounded-xl p-6">
            <!-- Company Logo -->
            <div class="flex justify-center mb-4 relative z-10">
                <img src="/asset/images/logo/logo.png" alt="Company Logo" class="h-8 w-auto">
            </div>
            <h2 class="text-xl text-center font-semibold text-[#0071BC] mb-4">Loan Details</h2>
            <form wire:submit.prevent="calculateEMI" class="space-y-6">
                <!-- Loan Amount Slider -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Loan Amount (₹)</label>
                    <input type="range" wire:model="principal" min="10000" max="10000000" step="1000"
                           class="w-full h-2 rounded-lg cursor-pointer" oninput="this.nextElementSibling.value = this.value">
                    <input type="number" wire:model="principal" min="10000" max="10000000" step="1000"
                           class="w-full mt-2 px-4 py-2 border rounded-md shadow-sm focus:ring focus:ring-indigo-300">
                </div>

                <!-- Interest Rate Slider -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Interest Rate (%)</label>
                    <input type="range" wire:model="rate" min="1" max="30" step="0.1"
                           class="w-full h-2 rounded-lg cursor-pointer" oninput="this.nextElementSibling.value = this.value">
                    <input type="number" wire:model="rate" step="0.01" min="1" max="30"
                           class="w-full mt-2 px-4 py-2 border rounded-md shadow-sm focus:ring focus:ring-indigo-300">
                </div>

                <!-- Duration Slider -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Duration (Months)</label>
                    <input type="range" wire:model="duration" min="6" max="360" step="1"
                           class="w-full h-2 rounded-lg cursor-pointer" oninput="this.nextElementSibling.value = this.value">
                    <input type="number" wire:model="duration" min="6" max="360"
                           class="w-full mt-2 px-4 py-2 border rounded-md shadow-sm focus:ring focus:ring-indigo-300">
                </div>

                <!-- Calculate Button -->
                <div class="flex justify-end">
                    <x-filament::button type="submit" class="bg-[#0071BC] hover:bg-[#005F99] text-white px-3 py-1 rounded-ms" style="background-color: #0071BC;" onmouseover="this.style.backgroundColor='red'" onmouseout="this.style.backgroundColor='#0071BC'">
                        Calculate EMI
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Right Side: EMI Details (Always Visible) -->
        <div class="bg-white shadow-lg rounded-xl p-6 relative overflow-hidden flex flex-col h-full">
            <!-- Background Color with Transparency -->
            <div class="absolute inset-0 bg-[#0071BC] opacity-20 rounded-xl z-0"></div>

            <!-- Company Logo -->
            <div class="flex justify-center mb-4 relative z-10">
                <img src="/asset/images/logo/logo.png" alt="Company Logo" class="h-8 w-auto">
            </div>

            <!-- EMI Display -->
            <div class="text-center mb-4 relative z-10">
                <h2 class="text-2xl font-semibold text-[#0071BC]">Monthly EMI</h2>
                <p class="text-3xl font-semibold text-gray-800">₹{{ number_format($emi, 2) }}</p>
            </div>

            <!-- EMI Details (Left Text & Right Amount) -->
            <div class="text-gray-700 space-y-3 relative z-10 flex-grow">
                <div class="flex justify-between">
                    <p>📌 <strong>Principal Amount:</strong></p>
                    <p class="font-semibold">₹{{ number_format($principal, 2) }}</p>
                </div>
                <div class="flex justify-between">
                    <p>🏦 <strong>Total Interest Payable:</strong></p>
                    <p class="font-semibold">₹{{ number_format($totalPayment - $principal, 2) }}</p>
                </div>
                <div class="flex justify-between">
                    <p>💰 <strong>Total Payment:</strong></p>
                    <p class="font-semibold">₹{{ number_format($totalPayment, 2) }}</p>
                </div>
            </div>
            <h3 class="text-xl font-bold text-[#0071BC] text-center mt-6 mb-3">Why Choose Our Loan?</h3>
            <ul class="list-none text-gray-700 pl-5 space-y-2">
                <li><strong>Customized EMI Plans</strong> – Allow users to choose step-up or step-down EMI options.</li>
                <li><strong>Special Discounts</strong> – If applicable, mention lower rates for salaried employees or women borrowers.</li>
                <li><strong>Auto-Debit Feature</strong> – Ensures hassle-free EMI payments.</li>
            </ul>
            <!-- Buttons at the Bottom -->
            <div class="flex justify-between mt-auto pt-6">
                <a href="{{ route('filament.admin.resources.loans.create') }}" class="no-underline">
                    <x-filament::button class="px-4 py-2 rounded-md text-white" style="background-color: #0071BC;" onmouseover="this.style.backgroundColor='red'" onmouseout="this.style.backgroundColor='#0071BC'">
                        Apply Now
                    </x-filament::button>
                </a>

                <a href="{{ route('filament.admin.resources.cibils.index') }}" class="no-underline">
                    <x-filament::button class="bg-[#0071BC] text-white px-4 py-2 rounded-md"
                                        style="background-color: #0071BC;"
                                        onmouseover="this.style.backgroundColor='red'"
                                        onmouseout="this.style.backgroundColor='#0071BC'">
                        Know More
                    </x-filament::button>
                </a>
            </div>
        </div>

    </div>

    <!-- Centered "View Amortization Schedule" Button -->
    <div class="text-center mt-6">
        <button wire:click="toggleAmortization"
                class="text-[#0071BC] font-semibold transition-colors duration-300"
                onmouseover="this.style.color='red'"
                onmouseout="this.style.color='#0071BC'">
            {{ $showAmortization ? 'Hide Amortization Schedule' : 'View Amortization Schedule' }}
        </button>
    </div>


    <!-- Amortization Schedule (Full Width) -->
    @if($showAmortization)
        <div class="mt-6 bg-white shadow-lg rounded-xl p-6">
            <h3 class="text-xl font-bold s text-[#0071BC] text-center mb-4">Amortization Schedule</h3>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-300 rounded-lg">
                    <thead>
                    <tr class="bg-[#0071BC] text-white">
                        <th class="p-3 border">Month</th>
                        <th class="p-3 border">Opening Balance</th>
                        <th class="p-3 border">Monthly Interest</th>
                        <th class="p-3 border">Monthly Principal</th>
                        <th class="p-3 border">Outstanding Balance</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($amortizationSchedule as $row)
                        <tr class="text-center border bg-gray-50 hover:bg-gray-100">
                            <td class="p-3 border">{{ $row['month'] }}</td>
                            <td class="p-3 border">₹ {{ number_format($row['opening_balance'], 2) }}</td>
                            <td class="p-3 border">₹ {{ number_format($row['monthly_interest'], 2) }}</td>
                            <td class="p-3 border">₹ {{ number_format($row['monthly_principal'], 2) }}</td>
                            <td class="p-3 border">₹ {{ number_format($row['outstanding_balance'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament::page>
