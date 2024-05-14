import React, { useState, useEffect } from 'react';
import DaylightChart from './DaylightChart';

const App = () => {
  const [daylightData, setDaylightData] = useState(null);
  const [daylightData2, setDaylightData2] = useState(null);
  const [, setCityName] = useState(null);
  const cityName = document
    .getElementById('react-root')
    .getAttribute('data-city-name');

  useEffect(() => {
    // Replace with your actual data fetching logic
    const fetchData = async () => {
      try {
        // Simulated fetch request
        const response = await fetch(
          `/api/daylight/${encodeURIComponent(cityName)}`
        );
        const data = await response.json();
        setDaylightData(data.daylightChanges);
        setCityName(data.cityName);
        const response2 = await fetch(
          `/api/daylight/${encodeURIComponent(cityName)}`
        );
        const data2 = await response2.json();
        setDaylightData2(data.daylightChanges2);
        setCityName(data.cityName2);
      } catch (error) {
        console.error('Failed to fetch data:', error);
      }
    };

    fetchData();
  }, [cityName,]);

  // Render the chart only when data is available
  return (
    <div>
      {daylightData && cityName ? (
        <DaylightChart daylightChanges={daylightData} daylightData2={daylightData2} cityName={cityName} />
      ) : (
        <p>Loading...</p>
      )}
    </div>
  );
};

export default App;
